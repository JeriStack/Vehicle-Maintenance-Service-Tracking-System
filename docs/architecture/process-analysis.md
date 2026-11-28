# Process Analysis & Workflow Architecture
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-PA-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS System Workflow, Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Executive Summary

This document defines the end-to-end business and technical processes for the **Vehicle Maintenance Service Tracking System (VMS)**. It bridges the approved Phase 1 requirements with technical system behaviors, formalizing the interaction pathways across the four execution contexts:
1. **`guest`** (Unauthenticated web visitor)
2. **`vehicle_owner`** (Authenticated vehicle owner)
3. **`system_admin`** (Authenticated platform administrator)
4. **`CLI scheduler`** (Restricted, trusted headless background execution identity)

---

## 2. Process Architecture & Execution Contexts

```text
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           VMS PROCESS CONTEXT MATRIX                            │
├──────────────┬──────────────────┬───────────────────────┬───────────────────────┤
│ Context      │ Entry Points     │ Data Boundary         │ State Transitions     │
├──────────────┼──────────────────┼───────────────────────┼───────────────────────┤
│ Guest        │ /auth/login.php  │ Public metadata only  │ Authentication /      │
│              │ /auth/register   │ Zero access to garage │ Registration to Owner │
│              │ /auth/forgot     │ or administration     │                       │
├──────────────┼──────────────────┼───────────────────────┼───────────────────────┤
│ Vehicle      │ /dashboard/*     │ Strict Owner-Scoped:  │ Active -> Archived    │
│ Owner        │ /vehicles/*      │ WHERE user_id = ?     │ Sched -> Completed    │
│              │ /services/*      │ Zero cross-user access│ Unread -> Read        │
│              │ /maintenance/*   │ Zero admin access     │                       │
├──────────────┼──────────────────┼───────────────────────┼───────────────────────┤
│ System       │ /admin/*         │ Platform Aggregations │ User Active/Inactive  │
│ Admin        │                  │ User accounts, Audit, │ Role User/Admin       │
│              │                  │ Catalog maintenance   │ Catalog Active/Inact  │
│              │                  │ NO access to invoices │                       │
├──────────────┼──────────────────┼───────────────────────┼───────────────────────┤
│ CLI          │ bin/process_     │ Headless Read/Write:  │ Sched -> Due/Overdue  │
│ Scheduler    │ reminders.php    │ Lock file (flock)     │ Insert Notification   │
│              │                  │ CLI-only restriction  │ Queue WS Broadcast    │
└──────────────┴──────────────────┴───────────────────────┴───────────────────────┘
```

---

## 3. Core Business Processes

### 3.1 Process 1: User Onboarding & Authentication Lifecycle
* **Trigger:** Guest visits portal to register or log in.
* **Process Steps:**
  1. *Registration:* Guest inputs full name, email, phone, and password. System normalizes email (`strtolower(trim())`), validates uniqueness, verifies password complexity, hashes password via bcrypt (`PASSWORD_BCRYPT`), and inserts with `role = 'user'`.
  2. *Authentication:* Guest submits credentials. System fetches user by normalized email, checks `status = 'active'`, and verifies password with `password_verify()`.
  3. *Session Establishment:* Session ID is regenerated (`session_regenerate_id(true)`), `$_SESSION['user_id']`, `$_SESSION['role']`, and `$_SESSION['csrf_token']` are populated.
  4. *Remember-Me:* If selected, a split-token (`selector:validator`) is generated, hashed, stored in `user_remember_tokens`, and sent as an `HttpOnly`, `SameSite=Lax` cookie.
  5. *Redirection:* Role-based routing: `admin` &rarr; `/admin/dashboard.php`; `user` &rarr; `/dashboard/index.php`.
  6. *Logout:* Session destroyed, remember cookie expired, DB token purged, redirect to `/auth/login.php`.

### 3.2 Process 2: Vehicle Lifecycle & Garage Management
* **Trigger:** Vehicle owner adds, modifies, or archives a personal vehicle.
* **Process Steps:**
  1. *Registration:* Owner enters plate, VIN, make, model, year, color, and odometer. System canonicalizes plate (`preg_replace('/[^A-Z0-9]/', '', strtoupper($plate))`), validates owner-scoped uniqueness, checks year bounds ($1886 \le \text{Year} \le \text{Current}+1$), and creates record with `status = 'Active'`.
  2. *Odometer Update:* Any manual odometer update checks non-regression ($\text{New Odometer} \ge \text{Current Odometer}$) and appends a row to `vehicle_mileage_logs`.
  3. *Archival (Soft-Delete):* Owner triggers deletion. System updates `vehicles.status = 'archived'`. Associated service records, schedules, and invoices remain intact. Active garage views filter out archived records (`WHERE status != 'archived'`).

### 3.3 Process 3: Service Logging & Monotonic Odometer Advancement
* **Trigger:** Vehicle owner records a completed maintenance service event.
* **Process Steps:**
  1. *Validation:* System checks that vehicle belongs to the authenticated user (`WHERE id = ? AND user_id = ?`), completed service date $\le$ current date (`Asia/Manila`), cost $\ge 0$ (stored in `DECIMAL(12,2)`), and service mileage is non-negative.
  2. *Database Transaction:*
     - Insert completed service entry into `service_records`.
     - Evaluate monotonic odometer: `UPDATE vehicles SET current_mileage = ? WHERE id = ? AND current_mileage < ?`.
     - Insert audit trail row into `vehicle_mileage_logs`.
     - If future target date/km provided, insert/update corresponding row in `maintenance_schedules`.
     - Log action in `audit_logs`.
     - Commit transaction.
  3. *Real-Time Broadcast:* Broadcaster pushes `service_created` event to active owner session over WebSocket and inserts into `realtime_events`.

### 3.4 Process 4: Dynamic Maintenance Status Evaluation ("Whichever Comes First")
* **Trigger:** Scheduled background daemon execution or owner loads maintenance dashboard.
* **Calculation Engine Logic:**
  ```text
  Input: target_date, target_mileage, current_mileage, reminder_lead_days, current_date
  
  Step 1: Evaluate Date Threshold
    If target_date < current_date: date_status = OVERDUE
    Else If target_date == current_date: date_status = DUE
    Else If target_date <= (current_date + reminder_lead_days): date_status = UPCOMING
    Else: date_status = NOT_DUE
    
  Step 2: Evaluate Mileage Threshold
    If current_mileage > target_mileage: mileage_status = OVERDUE
    Else If current_mileage == target_mileage: mileage_status = DUE
    Else If current_mileage >= (target_mileage - 1000 km): mileage_status = UPCOMING
    Else: mileage_status = NOT_DUE
    
  Step 3: Combine with Strict Precedence
    If (date_status == OVERDUE || mileage_status == OVERDUE) => FINAL_STATUS = OVERDUE
    Else If (date_status == DUE || mileage_status == DUE) => FINAL_STATUS = DUE
    Else If (date_status == UPCOMING || mileage_status == UPCOMING) => FINAL_STATUS = UPCOMING
    Else => FINAL_STATUS = NOT_DUE
  ```

### 3.5 Process 5: Automated Reminder & Notification Processing
* **Trigger:** Daily scheduled CLI execution (`php bin/process_reminders.php`).
* **Process Steps:**
  1. *Concurrency Guard:* Script acquires exclusive process lock (`flock($lockFile, LOCK_EX | LOCK_NB)`). Aborts if another instance is currently running.
  2. *Schedule Sweep:* Queries all active vehicles and open maintenance schedules.
  3. *Preference Evaluation:* Checks owner's `notification_settings` (`maintenance_reminder`, `overdue_reminder`, `registration_reminder`, `reminder_days`).
  4. *Deduplication Check:* Calculates deterministic hash:
     $$\text{dedupe\_key} = \text{sha256}(\text{user\_id} \mathbin{\Vert} \text{vehicle\_id} \mathbin{\Vert} \text{type} \mathbin{\Vert} \text{target\_date})$$
     Checks if unread notification with identical key exists within the last 7 days.
  5. *Notification Creation:* Inserts record into `notifications` and queues real-time push into `realtime_events`.
  6. *WebSocket Dispatch:* Broadcasts packet to active user channel.

### 3.6 Process 6: Administrative Governance & Privacy Enforcement
* **Trigger:** System Administrator inspects platform health or manages accounts.
* **Process Steps:**
  1. *Authorization:* `requireAdmin()` validates `$_SESSION['role'] === 'admin'`.
  2. *User Management:* Administrator searches users. Can toggle `status = 'active' | 'inactive'`.
  3. *Role Promotion / Demotion:* Administrator can promote a user to `admin` or demote an administrator to `user`.
     - *Invariant 1:* Administrator cannot change their own role (`self-demotion blocked`).
     - *Invariant 2:* Demotion blocked if `SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'` $\le 1$ (`last-admin protection`).
  4. *Privacy Enforcement:* Administrator dashboard displays platform statistics (total users, vehicles, services). Direct access to `/vehicles/view.php` or private repair notes is blocked by `requireUser()`.
  5. *Audit Trail:* All administrative actions write an entry into `audit_logs`.

