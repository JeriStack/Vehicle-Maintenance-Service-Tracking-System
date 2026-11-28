# Application Authorization Matrix & Access Control Rules
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-AM-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline, ADR-002  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Overview & Security Principles

This document establishes the server-side authorization policy for the Vehicle Maintenance Service Tracking System (VMS). In accordance with **ADR-002** (Role-Based Access Control and Session Lifecycle) and **ADR-003** (Strict Owner-Scoped Data Isolation), authorization is enforced strictly on the server before any data query, state transition, or privileged view render.

### Core Tenets:
1. **Zero Trust in Client-Side Controls:** Hidden buttons, disabled inputs, or CSS display toggles are never considered security controls. Every endpoint validates permissions independently.
2. **Strict Owner Isolation (IDOR Immunity):** Vehicle owners can only read, create, update, or archive resources that belong directly to their authenticated account (`user_id = $_SESSION['user_id']`).
3. **Admin Privacy Boundary:** System Administrators have platform-wide governance rights (user management, global catalog, system configuration, audit logs) but are **strictly barred** from viewing private owner vehicle repair details, maintenance history, invoices, or personal reminder inboxes.
4. **Idempotent CLI Isolation:** Automated tasks run strictly in the CLI execution context (`php_sapi_name() === 'cli'`). No web-facing execution triggers or unauthenticated webhook endpoints exist.
5. **Safe HTTP Verbs:** State-changing operations (mutation, creation, deletion, status changes) are strictly rejected on `GET` requests and require authenticated `POST` requests guarded by valid CSRF tokens.

---

## 2. Global Role & Context Authorization Matrix

The table below defines application-wide operational permissions across the four execution contexts:
- **GUEST:** Unauthenticated public visitor.
- **VEHICLE_OWNER:** Authenticated individual owner (`role = 'user'`).
- **SYSTEM_ADMIN:** Authenticated platform administrator (`role = 'admin'`).
- **CLI_SCHEDULER:** Headless command-line daemon running scheduled maintenance and reminder routines.

| Operation / Domain | GUEST | VEHICLE_OWNER | SYSTEM_ADMIN | CLI_SCHEDULER | Enforcement Mechanism |
|---|---|---|---|---|---|
| **View Login / Register Cards** | **ALLOW** | Deny (Redirect Dashboard) | Deny (Redirect Admin) | Not Applicable | Session existence check (`AuthHelper::guestOnly()`) |
| **Authenticate (Login)** | **ALLOW** | Allow (Re-auth) | Allow (Re-auth) | Not Applicable | Rate limit + password verify + session regeneration |
| **Register New Account** | **ALLOW** | Deny (Redirect) | Deny (Redirect) | Not Applicable | CSRF + Input validation + auto-assigned `role = 'user'` |
| **Request Password Reset** | **ALLOW** | Allow | Allow | Not Applicable | Rate limit + token generation (`password_resets`) |
| **Reset Password via Token** | **ALLOW** | Allow | Allow | Not Applicable | Unexpired hash match + session termination |
| **View Owner Dashboard** | Deny (401) | **ALLOW (Own Data)** | Deny (403 Admin Redirect) | Not Applicable | `AuthHelper::requireRole('user')` |
| **Manage Profile / Settings** | Deny (401) | **ALLOW (Own Account)** | **ALLOW (Own Account)** | Not Applicable | `AuthHelper::requireAuth()` |
| **Create Vehicle** | Deny (401) | **ALLOW** | Deny (403) | Not Applicable | `AuthHelper::requireRole('user')` + CSRF |
| **View Vehicle List / Details** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (Privacy Boundary) | Read-only (Batch) | `WHERE user_id = :session_user_id` |
| **Edit / Update Vehicle** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Ownership verification before update + CSRF |
| **Archive Vehicle (Soft Delete)** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Ownership verification + status update + CSRF |
| **Restore Archived Vehicle** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Ownership verification + status restore + CSRF |
| **View Service Records** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (Privacy Boundary) | Not Applicable | JOIN vehicle verification (`v.user_id = :uid`) |
| **Create Service Record** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Transactional ownership check + odometer advance |
| **Void Service Record** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Ownership check + reason requirement + CSRF |
| **View Maintenance Schedules**| Deny (401) | **ALLOW (Own Vehicles)** | Deny (Privacy Boundary) | Read-only (Batch) | Dynamic status calculation + owner-scoped |
| **Create / Edit Schedules** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Vehicle ownership check + CSRF |
| **Log Mileage Increments** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (403) | Not Applicable | Vehicle ownership check + monotonic validation |
| **View Notification Inbox** | Deny (401) | **ALLOW (Own Notifications)**| Deny (Privacy Boundary) | Not Applicable | `WHERE user_id = :session_user_id` |
| **Mark Notification Read** | Deny (401) | **ALLOW (Own Notifications)**| Deny (Privacy Boundary) | Not Applicable | Ownership check + CSRF / AJAX token |
| **Generate Vehicle Reports** | Deny (401) | **ALLOW (Own Vehicles)** | Deny (Privacy Boundary) | Not Applicable | Scoped aggregate query (`v.user_id = :uid`) |
| **View Admin Dashboard** | Deny (401) | Deny (403) | **ALLOW** | Not Applicable | `AuthHelper::requireRole('admin')` |
| **Search / List All Users** | Deny (401) | Deny (403) | **ALLOW** | Not Applicable | `AuthHelper::requireRole('admin')` |
| **Toggle User Status (Ban/Active)**| Deny (401) | Deny (403) | **ALLOW (Safeguarded)** | Not Applicable | Self-deactivation blocked + CSRF + Audit |
| **Promote / Demote Admin Role**| Deny (401) | Deny (403) | **ALLOW (Safeguarded)** | Not Applicable | Last-active-admin check + CSRF + Audit |
| **Manage Global Catalog** | Deny (401) | Deny (403) | **ALLOW** | Read-only (Active)| `AuthHelper::requireRole('admin')` + CSRF |
| **Inspect System Audit Logs** | Deny (401) | Deny (403) | **ALLOW (Read-Only)** | Write-only (Logging) | Immutable audit repository + no edit/delete |
| **Inspect System Configuration**| Deny (401) | Deny (403) | **ALLOW** | Read-only | `AuthHelper::requireRole('admin')` |
| **Run Reminder Evaluation** | Deny (CLI Only) | Deny (CLI Only) | Deny (CLI Only) | **ALLOW (CLI Lock)** | `php_sapi_name() === 'cli'` + `flock` lock |
| **Broadcast WebSocket Events** | Deny (Internal)| Deny (Internal) | Deny (Internal) | System Helper Only | Internal loopback or direct queue insertion |

---

## 3. Explicit Sensitive Operation Guards

Every sensitive operation in VMS enforces mandatory server-side precondition checks:

### 3.1 Vehicle Resource Operations
- **Precondition:** Before executing any read, edit, update, or soft-archival query for `vehicle_id = :vid`, the handler queries:
  ```sql
  SELECT id FROM vehicles WHERE id = :vid AND user_id = :session_user_id AND status != 'deleted'
  ```
- **Failure Behavior:** If 0 rows return, the handler immediately terminates with `HTTP 404 Not Found` (to prevent ID enumeration) or `HTTP 403 Forbidden`, and writes a security violation entry to `audit_logs`.

### 3.2 Service Record Mutations
- **Precondition:** When creating, viewing, or voiding a service record for `service_id = :sid`:
  ```sql
  SELECT sr.id, sr.vehicle_id 
  FROM service_records sr
  INNER JOIN vehicles v ON sr.vehicle_id = v.id
  WHERE sr.id = :sid AND v.user_id = :session_user_id
  ```
- **Monotonic Odometer Rule:** On record creation, the service mileage must satisfy `service_mileage >= v.current_mileage`. If valid, the transaction updates `vehicles.current_mileage = service_mileage` and logs an entry in `vehicle_mileage_logs`.
- **Voiding Rule:** Service records cannot be physically deleted. Voiding requires `status = 'Voided'`, an explicit non-empty `void_reason` parameter, and an audit trail entry.

### 3.3 Maintenance Schedule Mutations
- **Precondition:** Schedules are verified through their parent vehicle:
  ```sql
  SELECT ms.id 
  FROM maintenance_schedules ms
  INNER JOIN vehicles v ON ms.vehicle_id = v.id
  WHERE ms.id = :sched_id AND v.user_id = :session_user_id
  ```
- **Dual-Criteria Integrity:** At least one threshold (`interval_months > 0` OR `interval_mileage > 0`) must be specified.

### 3.4 In-App Notification Mutators
- **Precondition:** Read markers and dismissals require:
  ```sql
  UPDATE notifications 
  SET is_read = 1, read_at = NOW() 
  WHERE id = :notification_id AND user_id = :session_user_id
  ```
- Attempting to mark another user's notification returns 0 affected rows and halts without leaking existence.

### 3.5 System Administrator Account Lifecycle & Role Changes
- **Target Role Modification:**
  - An administrator can never demote their own account from `admin` to `user` in the web session.
  - An administrator cannot demote or deactivate the last remaining active administrator:
    ```sql
    SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND id != :target_user_id
    ```
    If count equals 0, the operation aborts with a validation error: `"Cannot demote or deactivate the last remaining system administrator."`
- **Audit Requirement:** Every user role modification, account suspension, or reactivation generates an immutable audit record capturing `admin_id`, `target_user_id`, `action`, `previous_state`, `new_state`, and `client_ip`.

### 3.6 CLI Scheduler Execution Boundaries
- **Environment Guard:**
  ```php
  if (php_sapi_name() !== 'cli') {
      http_response_code(403);
      die("Forbidden: This command can only be executed via the command line interface.");
  }
  ```
- **Concurrency Lock:** Evaluator scripts acquire an exclusive file lock (`flock($fp, LOCK_EX | LOCK_NB)`) on `cron_reminders.lock`. If locked, the process exits cleanly with status 0 to prevent overlapping duplicate runs.
- **Deduplication Idempotency:** Notifications use a unique composite deduplication key:
  ```text
  dedupe_key = hash('sha256', vehicle_id . '|' . schedule_id . '|' . trigger_type . '|' . due_date)
  ```
  If a record exists in `notifications` with the same `dedupe_key`, creation is skipped.

---

## 4. Route-to-Authorization Verification Checklist

| Security Gate | Verification Method | Automated Test Location |
|---|---|---|
| **Guest Route Protection** | Verify authenticated users are redirected away from login/register | `tests/Unit/AuthTest.php` |
| **Owner Data Isolation** | Inject mismatched `user_id` / `vehicle_id` and assert `403` or `404` | `tests/Unit/AuthorizationTest.php` |
| **Admin Privacy Barrier** | Assert admin session cannot query `/reports/` or `/vehicles/view.php` | `tests/Unit/AdminPrivacyTest.php` |
| **CSRF Validation** | Submit state-changing POST without `csrf_token` and assert `403` | `tests/Unit/CsrfValidationTest.php` |
| **Last Admin Guard** | Attempt to demote sole admin and assert operation rejection | `tests/Unit/AdminGovernanceTest.php` |
| **CLI Web Inaccessibility**| Send HTTP GET/POST to `cron_reminders.php` and assert `403` | `tests/Unit/CliSecurityTest.php` |

---

## 5. Traceability to Requirements & Backlog

- **FR-AUTH-01 / FR-AUTH-04:** Enforced in Sections 2 and 3.5 (RBAC and Administrator governance).
- **FR-VEH-01 / FR-VEH-05:** Enforced in Section 3.1 (Vehicle ownership validation and soft-delete protection).
- **FR-SRV-01 / FR-SRV-04:** Enforced in Section 3.2 (Transactional service record ownership and monotonic odometer).
- **FR-NOTIF-03:** Enforced in Section 3.6 (CLI execution lock and deduplication key).
- **NFR-SEC-01 / NFR-SEC-03:** Enforced in Sections 1, 2, and 3 (Zero-trust server-side checks and CSRF defense).
- **Backlog Tasks:** `US-SEC-01`, `US-SEC-02`, `US-ADM-02`, `US-VEH-03`, `US-SRV-02`.

