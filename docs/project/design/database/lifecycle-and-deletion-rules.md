# Lifecycle & Deletion Rules Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-LDR-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Core Data Integrity Philosophy

A vehicle maintenance system is a **financial and safety record of provenance**. Cascading hard deletions of vehicles or service invoices corrupt lifetime financial analytics, destroy warranty documentation, and eliminate auditable repair trails. 

Therefore, VMS enforces **Soft-Archival and Auditable Invalidation Policies** across all primary business entities.

---

## 2. Entity-by-Entity Lifecycle & Deletion Policies

```text
┌────────────────────────────────────────────────────────────────────────┐
│                     VMS DELETION POLICY MATRIX                         │
├─────────────────────┬──────────────────┬───────────────────────────────┤
│ Entity              │ Allowed Action   │ System Behavior & Invariant   │
├─────────────────────┼──────────────────┼───────────────────────────────┤
│ users               │ Deactivation     │ status = 'inactive'; all data │
│                     │                  │ preserved; auth blocked       │
├─────────────────────┼──────────────────┼───────────────────────────────┤
│ vehicles            │ Soft-Archival    │ status = 'archived'; services │
│                     │                  │ & schedules fully preserved   │
├─────────────────────┼──────────────────┼───────────────────────────────┤
│ service_records     │ Auditable Void   │ status = 'Voided'; void_reason│
│                     │                  │ logged; excluded from sums    │
├─────────────────────┼──────────────────┼───────────────────────────────┤
│ maintenance_types   │ Deactivation     │ is_active = 0; past services  │
│                     │                  │ retain catalog reference      │
├─────────────────────┼──────────────────┼───────────────────────────────┤
│ maintenance_sched   │ State Transition │ status = COMPLETED/CANCELLED; │
│                     │                  │ historical provenance retained│
├─────────────────────┼──────────────────┼───────────────────────────────┤
│ audit_logs          │ Immutable Append │ Zero deletion; immutable logs │
└─────────────────────┴──────────────────┴───────────────────────────────┘
```

### 2.1 Users (`users`)
* **Policy:** **Deactivation over Deletion**.
* **Behavior:** When an administrator terminates access, `users.status` is set to `'inactive'`. The user's vehicles, service histories, and audit records remain intact. The user is immediately blocked from logging in or using remember-me sessions.
* **Permanent Retention Purge:** Only permitted under a formal GDPR/privacy "Right to be Forgotten" procedure approved by an administrator, which anonymizes personally identifiable information (`full_name = 'Anonymized User'`, `email = 'purged-id@vms.local'`) while retaining numerical service totals for aggregate platform accounting.

### 2.2 Vehicles (`vehicles`)
* **Policy:** **Soft-Archival (`status = 'archived'`)**.
* **Behavior:** When an owner clicks "Delete Vehicle", the application issues `UPDATE vehicles SET status = 'archived' WHERE id = ? AND user_id = ?`.
* **Invariants:**
  1. The vehicle is excluded from the active garage grid and active dashboard count queries (`WHERE status != 'archived'`).
  2. All associated `service_records`, `maintenance_schedules`, and `vehicle_mileage_logs` are preserved.
  3. Lifetime cost reports continue to reflect historical maintenance expenditures incurred by the vehicle.
  4. New service logging against an archived vehicle is blocked.

### 2.3 Service Records (`service_records`)
* **Policy:** **Auditable Invalidation (Voiding)**.
* **Behavior:** Completed service records must never be deleted through SQL `DELETE`. If a transaction was entered erroneously or duplicated, the owner initiates a void action providing a mandatory reason (`void_reason`). The system updates `status = 'Voided'`.
* **Invariants:**
  1. Voided records are displayed with a strikethrough or "Voided" badge in service history.
  2. Financial analytics queries (`SUM(cost)`) strictly filter `WHERE status = 'Completed'`, preventing voided records from distorting lifetime expense summaries.
  3. The voiding event is recorded in `audit_logs` with the actor ID and timestamp.

### 2.4 Maintenance Types (`maintenance_types`)
* **Policy:** **Logical Deactivation (`is_active = FALSE`)**.
* **Behavior:** Global catalog items are never deleted if they have been referenced by any historical service record or schedule. Deactivating an item hides it from new service dropdowns while historical records continue to resolve their foreign key reference cleanly.

### 2.5 Maintenance Schedules (`maintenance_schedules`)
* **Policy:** **Explicit Lifecycle Transitions**.
* **Behavior:** When a maintenance task is executed, logging the completed service updates the schedule status to `'COMPLETED'` and records `completed_at`. If an owner dismisses a non-applicable schedule, it transitions to `'CANCELLED'`. Open schedules remain `'ACTIVE'`.

### 2.6 Security & Administrative Audit Logs (`audit_logs`)
* **Policy:** **Strict Append-Only Immutability**.
* **Behavior:** No endpoint, controller, or administrative UI component permits updating (`UPDATE`) or deleting (`DELETE`) rows in `audit_logs`.
* **Invariants:**
  1. Even system administrators with elevated privileges cannot alter audit history.
  2. Deleting a user sets `audit_logs.user_id = NULL` via foreign key `ON DELETE SET NULL`, while retaining the action description, record ID, and immutable timestamp.

