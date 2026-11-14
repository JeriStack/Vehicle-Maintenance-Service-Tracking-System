# Requirements Baseline Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-REQ-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Authority:** VMS Complete Plan, UML Blueprints, Phase 0 Project Baseline  
**Status:** Baseline Established / Pending Stakeholder Sign-Off  
**Last Updated:** 2026-11-05  

---

## 1. Executive Summary & Purpose

This document establishes the official, testable, and prioritized **Requirements Baseline** for the **Vehicle Maintenance Service Tracking System (VMS)**. It synthesizes the requirements from the 32-page *VMS Complete Plan — Administrator Edition*, the *VMS UML Diagrams*, the *VMS System Workflow*, and the verified Phase 0 codebase audit.

Every requirement preserves its standardized category identifier, assigns an accountable role, defines clear business justifications, establishes concrete acceptance criteria, and maps to its designated release priority (`P0 - MVP`, `P1 - Release 1.1`, or `P2 - Optional / Future`).

---

## 2. User Roles & Permission Boundaries

```text
┌────────────────────────────────────────────────────────────────────────┐
│                   VMS TWO-ROLE PERMISSION MODEL                        │
├───────────────────────────────────┬────────────────────────────────────┤
│           SYSTEM_ADMIN            │           VEHICLE_OWNER            │
├───────────────────────────────────┼────────────────────────────────────┤
│ • Platform health & metrics       │ • Account self-registration        │
│ • User account lifecycle mgmt     │ • Personal vehicle garage CRUD     │
│ • Account activate / deactivate   │ • Completed maintenance logging    │
│ • Admin privilege management      │ • Next service date/km projections │
│ • Last active admin protection    │ • Real-time alerts & reminders     │
│ • Global service type catalog     │ • Registration expiry tracking     │
│ • Security audit log inspection   │ • Personal cost & history reports  │
│ • System-wide configurations      │ • CSV data export                  │
│ [X] NO ACCESS TO PRIVATE RECORDS  │ [X] STRICT OWNER-SCOPED ISOLATION  │
└───────────────────────────────────┴────────────────────────────────────┘
```

> [!IMPORTANT]
> **Non-Login Actors:** Mechanics, workshops, and dealerships are **NOT** application roles. They are recorded exclusively as descriptive text metadata (`service_provider`) on service transactions.

---

## 3. Functional Requirements Catalog

### 3.1 Authentication & Account Security (`AUTH`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **AUTH-001** | User Registration | Guest | P0 | MVP | Allow vehicle owners to register an account with name, email, phone, and password. | Password >= 6 chars; email normalized; default role = `VEHICLE_OWNER` (`user`). | **IMPLEMENTED** (`auth/register.php`) |
| **AUTH-002** | Secure Login | Guest | P0 | MVP | Authenticate existing users via email and password with `password_verify()`. | Generates new session ID; invalid credentials return generic error; redirects by role. | **IMPLEMENTED** (`auth/login.php`) |
| **AUTH-003** | User Logout | Authenticated | P0 | MVP | Terminate active user session and invalidate remember-me tokens. | `$_SESSION` destroyed; session cookie cleared; DB token revoked; redirect to login. | **IMPLEMENTED** (`auth/logout.php`) |
| **AUTH-004** | Password Hashing | System | P0 | MVP | Enforce cryptographic hashing on all stored credentials. | `password_hash()` with `PASSWORD_BCRYPT` (cost 10+); zero plaintext stored. | **IMPLEMENTED** (`Database & Actions`) |
| **AUTH-005** | Remember-Me Persistent Login | User / Admin | P0 | MVP | Allow users to remain logged in across browser sessions via secure split-tokens. | Split-token (`selector` + `hashed_validator`); 30-day expiry; rotated on use. | **IMPLEMENTED** (`includes/auth.php`) |
| **AUTH-006** | Self-Service Password Reset | Guest | P0 | MVP | Enable users to request a password reset link sent to their email. | 60-min SHA-256 token; email dispatched via SMTP/mail log; password updated. | **IMPLEMENTED** (`auth/forgot_password.php`) |
| **AUTH-007** | Role-Based Access Control | System | P0 | MVP | Restrict route and action access based strictly on authenticated role. | `requireAdmin()` blocks users; `requireUser()` blocks admins; 403 Forbidden on breach. | **IMPLEMENTED** (`includes/auth.php`) |
| **AUTH-008** | Session Fixation Protection | System | P0 | MVP | Regenerate session identifiers on privilege transitions. | `session_regenerate_id(true)` called upon login, logout, and role switch. | **IMPLEMENTED** (`actions/login_action.php`) |
| **AUTH-009** | CSRF Token Defense | System | P0 | MVP | Protect all state-altering POST/PUT requests against CSRF attacks. | Cryptographic token in `$_SESSION['csrf_token']`; verified in every POST action. | **IMPLEMENTED** (`All Actions`) |

### 3.2 Vehicle Management (`VEH`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **VEH-001** | Register Vehicle | Vehicle Owner | P0 | MVP | Add personal vehicles with plate, VIN, make, model, year, color, and mileage. | Plate required; year valid (1886–current+1); odometer >= 0; scoped to user ID. | **IMPLEMENTED** (`vehicles/create.php`) |
| **VEH-002** | View Personal Garage | Vehicle Owner | P0 | MVP | Display all active vehicles owned by the authenticated user. | Lists vehicles with status badges, odometer, and plate; owner-scoped query. | **IMPLEMENTED** (`vehicles/index.php`) |
| **VEH-003** | Update Vehicle Information | Vehicle Owner | P0 | MVP | Modify existing vehicle attributes (plate, color, registration dates, odometer). | Updates allowed only by vehicle owner; odometer cannot regress below prior reading. | **IMPLEMENTED** (`vehicles/edit.php`) |
| **VEH-004** | Archive Vehicle (Soft-Delete) | Vehicle Owner | P0 | MVP | Deactivate a vehicle without destroying historical service logs or invoices. | Sets `status = 'archived'`; excluded from active counts; historical records intact. | **PARTIALLY IMPLEMENTED** (Needs update from SQL DELETE) |
| **VEH-005** | Vehicle IDOR Ownership Guard | System | P0 | MVP | Enforce database-level ownership checks preventing cross-user vehicle tampering. | Insecure direct object reference returns HTTP 403 / Permission Denied redirect. | **IMPLEMENTED** (`actions/vehicle_action.php`) |
| **VEH-006** | Plate Normalization & Uniqueness | System | P0 | MVP | Canonicalize plate strings (e.g. " abc-1234 " -> "ABC1234") and enforce uniqueness per owner. | Normalized plate must be unique per owner; two different owners may share a plate. | **PARTIALLY IMPLEMENTED** (Trim/strtoupper used; need canonical rule) |
| **VEH-007** | Odometer Audit History Log | System | P1 | Release 1.1 | Track historical odometer adjustments in a dedicated audit log table. | Appends entry to `vehicle_mileage_logs` with timestamp, source, and reading. | **PARTIALLY IMPLEMENTED** (Table migration created) |

### 3.3 Service Records & Service History (`SRV`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **SRV-001** | Record Completed Service | Vehicle Owner | P0 | MVP | Log maintenance event with type, date, odometer, cost, provider, parts, and notes. | Completed date <= today; cost >= 0; monotonic odometer update; owner-scoped. | **IMPLEMENTED** (`services/create.php`) |
| **SRV-002** | View Service History | Vehicle Owner | P0 | MVP | Display chronologically sorted service history for personal vehicles. | Filterable by vehicle, service type, and date; shows costs and provider names. | **IMPLEMENTED** (`services/index.php`) |
| **SRV-003** | Edit Service Record | Vehicle Owner | P0 | MVP | Update notes, cost, or parts for an existing completed service record. | Restricted to record owner; cannot regress odometer; validates decimal cost. | **IMPLEMENTED** (`services/edit.php`) |
| **SRV-004** | Void Incorrect Service Record | Vehicle Owner | P1 | Release 1.1 | Mark a faulty service record as "Voided" with a mandatory explanation reason. | Preserved in history; excluded from financial totals; status = 'Voided'. | **NOT IMPLEMENTED** (Currently uses SQL DELETE) |
| **SRV-005** | Monotonic Odometer Progression | System | P0 | MVP | Ensure completed service odometer updates master vehicle mileage forward. | If service mileage > vehicle current mileage, vehicle odometer is updated. | **IMPLEMENTED** (`actions/service_action.php`) |
| **SRV-006** | Provider & Parts Metadata | Vehicle Owner | P0 | MVP | Capture mechanic/shop name and part replacement list as descriptive text. | Sanitized text inputs; escaped on display with `htmlspecialchars()`. | **IMPLEMENTED** (`services/create.php`) |
| **SRV-007** | Monetary Decimal Precision | System | P0 | MVP | Store financial figures using exact `DECIMAL(10,2)` or `DECIMAL(12,2)`. | No float/double rounding errors; formatted with Philippine Peso `₱` symbol. | **IMPLEMENTED** (`schema.sql` & `FunctionsTest`) |

### 3.4 Maintenance Tracking & Scheduling (`MTN`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **MTN-001** | Dual-Criteria Maintenance Scheduling | Vehicle Owner | P0 | MVP | Define future maintenance targets by date, mileage, or both simultaneously. | At least one valid trigger required (date or km); triggers evaluated independently. | **IMPLEMENTED** (`services/create.php`) |
| **MTN-002** | Status Calculation Engine | System | P0 | MVP | Evaluate maintenance status using the "whichever comes first" rule. | Returns `OVERDUE`, `DUE`, `UPCOMING`, or `NOT_DUE` based on thresholds. | **IMPLEMENTED** (`helpers/maintenance_calculator.php`) |
| **MTN-003** | Upcoming Maintenance View | Vehicle Owner | P0 | MVP | Display maintenance schedules entering the advance reminder window. | Shows services due within 7–30 days or within 500–1,000 km. | **IMPLEMENTED** (`maintenance/upcoming.php`) |
| **MTN-004** | Due Maintenance Monitoring | Vehicle Owner | P0 | MVP | Highlight maintenance items due today or at exact mileage limit. | Visual badge `DUE TODAY`; priority sorting on dashboard. | **IMPLEMENTED** (`maintenance/due.php`) |
| **MTN-005** | Overdue Maintenance Alerting | Vehicle Owner | P0 | MVP | Display maintenance items that have exceeded date or odometer limits. | Visual badge `OVERDUE`; instant alerting; top priority in notifications. | **IMPLEMENTED** (`maintenance/overdue.php`) |
| **MTN-006** | Dedicated Schedules Entity | System | P1 | Release 1.1 | Decouple future maintenance schedules from past service records into `maintenance_schedules`. | Full CRUD on future schedules; closing a schedule logs a service record. | **PARTIALLY IMPLEMENTED** (Table migration created) |

### 3.5 Notifications & Reminders (`NTF`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **NTF-001** | In-App Notification Center | Vehicle Owner | P0 | MVP | Unified feed of active maintenance alerts and registration warnings. | Displays title, message, date, unread badge; links to relevant vehicle/service. | **IMPLEMENTED** (`notifications/index.php`) |
| **NTF-002** | Maintenance Service Reminders | System | P0 | MVP | Generate automated in-app alerts when maintenance becomes upcoming/due/overdue. | Respects user lead-days; triggered via scheduler or page refresh. | **IMPLEMENTED** (`helpers/notification_helper.php`) |
| **NTF-003** | Registration Renewal Reminders | System | P0 | MVP | Generate automated alerts when vehicle registration is expiring soon (<= 30 days). | Notifies user prior to expiry; flags expired registration as overdue. | **IMPLEMENTED** (`helpers/notification_helper.php`) |
| **NTF-004** | Idempotent Reminder Deduplication | System | P0 | MVP | Prevent duplicate notification spam on repeated scheduler executions. | Unique deduplication key (`dedupe_key`); 7-day suppression window. | **IMPLEMENTED** (`helpers/notification_helper.php`) |
| **NTF-005** | Real-Time WebSocket Alerts | User / Admin | P0 | MVP | Deliver instantaneous desktop notifications via native RFC 6455 WebSockets. | Non-blocking loopback stream; Web Audio chime; floating toast; DB fallback. | **IMPLEMENTED** (`websocket/server.php`) |
| **NTF-006** | Mark Notification Read/Delete | Vehicle Owner | P0 | MVP | Allow users to mark notifications as read or dismiss them from feed. | `is_read = 1`; deletes owned notification only; updates navbar badge. | **IMPLEMENTED** (`notifications/mark_read.php`) |
| **NTF-007** | SMTP Email Notification Dispatch | System | P1 | Release 1.1 | Send email copies of critical overdue maintenance alerts via native SMTP. | Dispatches email when `email_notifications = 1`; fallback to `logs/mail.log`. | **PARTIALLY IMPLEMENTED** (Mailer ready; cron hook in P1) |

### 3.6 Dashboards & Summaries (`DASH`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **DASH-001** | Vehicle Owner Dashboard | Vehicle Owner | P0 | MVP | Central dashboard displaying personal vehicle counts, recent services, and alerts. | Summary cards (Total Vehicles, Active Reminders, Overdue, Total Spent). | **IMPLEMENTED** (`dashboard/index.php`) |
| **DASH-002** | Real-Time Status Counters | Vehicle Owner | P0 | MVP | Dynamic counters for overdue, due, and upcoming maintenance. | Auto-updated via WebSocket events without requiring manual browser reload. | **IMPLEMENTED** (`assets/js/notifications.js`) |
| **DASH-003** | Workflow Shortcuts | Vehicle Owner | P0 | MVP | One-click access to add vehicle, log service, and view due maintenance. | Prominent action buttons styled with responsive Bootstrap 5 components. | **IMPLEMENTED** (`dashboard/index.php`) |
| **DASH-004** | Administrator Platform Dashboard | Admin | P0 | MVP | High-level platform health overview for administrators. | Total platform users, active vehicles, completed services, system logs. | **IMPLEMENTED** (`admin/dashboard.php`) |

### 3.7 Reporting & Analytics (`RPT`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **RPT-001** | Maintenance Summary Report | Vehicle Owner | P0 | MVP | Aggregated maintenance metrics grouped by vehicle. | Total services, completed count, total cost in ₱, upcoming/due metrics. | **IMPLEMENTED** (`reports/maintenance.php`) |
| **RPT-002** | Chronological Service Log | Vehicle Owner | P0 | MVP | Detailed list of all completed maintenance transactions with filter controls. | Filter by date range, vehicle ID, service type; displays cost and provider. | **IMPLEMENTED** (`reports/service_history.php`) |
| **RPT-003** | Monthly & Yearly Cost Analysis | Vehicle Owner | P0 | MVP | Visual expenditure trends over monthly and yearly calendar spans. | Interactive Chart.js bar and line charts; monthly average spending. | **IMPLEMENTED** (`reports/costs.php`, `monthly.php`) |
| **RPT-004** | Vehicle-Specific Filtering | Vehicle Owner | P0 | MVP | Filter all reporting views to inspect a single designated vehicle. | Owner-scoped query (`WHERE v.user_id = ? AND v.id = ?`). | **IMPLEMENTED** (`reports/costs.php`) |
| **RPT-005** | RFC 4180 CSV Data Export | Vehicle Owner | P1 | Release 1.1 | Download maintenance summaries and service history logs as CSV files. | Standard CSV headers; properly quoted text; escaped commas/newlines. | **IMPLEMENTED** (`reports/export_csv.php`) |
| **RPT-006** | Print-Optimized / PDF Export | Vehicle Owner | P2 | Optional | Clean print stylesheet and PDF export for physical maintenance record-keeping. | `@media print` CSS rules; PDF generation. | **NOT IMPLEMENTED** |

### 3.8 Settings & User Preferences (`SET`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **SET-001** | User Profile Management | User / Admin | P0 | MVP | Allow users to view and update full name and contact phone number. | Name required; phone sanitized; updates `users` record; audit logged. | **IMPLEMENTED** (`settings/profile.php`) |
| **SET-002** | Account Password Change | User / Admin | P0 | MVP | Update account password after verifying the existing current password. | Current password verified with `password_verify()`; new password hashed. | **IMPLEMENTED** (`settings/password.php`) |
| **SET-003** | Notification Preferences | Vehicle Owner | P0 | MVP | Configure reminder toggles (service, registration, overdue) and lead days. | Lead days between 1–30; boolean toggles; stored in `notification_settings`. | **IMPLEMENTED** (`settings/notifications.php`) |
| **SET-004** | Vehicle Servicing Defaults | Vehicle Owner | P1 | Release 1.1 | Set personal default service intervals (e.g. default 6 months / 5,000 km). | Stored in `vehicle_preferences`; auto-populates service logging forms. | **IMPLEMENTED** (`settings/vehicle_preferences.php`) |

### 3.9 System Administration (`ADM`)

| Req ID | Title | User Role | Priority | Release | Description | Acceptance Criteria Summary | Existing Implementation Status |
|---|---|---|---|---|---|---|---|
| **ADM-001** | User Directory & Search | Admin | P0 | MVP | List and search all registered platform user accounts by name or email. | Searchable user table; displays role, registration date, status. | **IMPLEMENTED** (`admin/users.php`) |
| **ADM-002** | Account Activation / Deactivation | Admin | P0 | MVP | Toggle user account status between 'active' and 'inactive'. | Deactivated users blocked at login; action recorded in audit log. | **PARTIALLY IMPLEMENTED** (Check in auth; UI toggle needed) |
| **ADM-003** | Admin Privilege Management | Admin | P0 | MVP | Promote a user to administrator or demote an administrator to standard user. | Validates role input; requires confirmation; records event in `audit_logs`. | **IMPLEMENTED** (`actions/admin_user_action.php`) |
| **ADM-004** | Last-Administrator Protection | System | P0 | MVP | Prevent self-demotion or demotion of the final active system administrator. | SQL count check blocks demotion if total active admins <= 1. | **IMPLEMENTED** (`actions/admin_user_action.php`) |
| **ADM-005** | Global Service Type Catalog | Admin | P1 | Release 1.1 | Manage standard global maintenance types (Oil Change, Brake Inspection, etc.). | Admin CRUD on `maintenance_types`; toggles `is_active`; sets default days/km. | **PARTIALLY IMPLEMENTED** (Schema migration ready) |
| **ADM-006** | Security Audit Log Viewer | Admin | P0 | MVP | Inspect system-wide audit logs with timestamps, actor IDs, and actions. | Filterable audit log table; captures logins, role changes, record mutations. | **IMPLEMENTED** (`admin/audit_logs.php`) |
| **ADM-007** | Owner Privacy Boundary | System | P0 | MVP | Prevent administrators from inspecting private owner service details. | Admin views show platform aggregated metadata; private records inaccessible. | **PARTIALLY IMPLEMENTED** (Admin views require scoping update) |
| **ADM-008** | System Settings Management | Admin | P1 | Release 1.1 | Oversee platform-level configuration parameters (app name, maintenance mode). | Stored in system settings table; restricted to administrators. | **PARTIALLY IMPLEMENTED** (`admin/settings.php`) |

---

## 4. Non-Functional Requirements (NFR)

| NFR ID | Category | Requirement Description | Verification Method |
|---|---|---|---|
| **NFR-SEC-01** | Cryptographic Hashing | Passwords must use `PASSWORD_BCRYPT` with cost >= 10. | `ValidationTest` & Auth unit tests |
| **NFR-SEC-02** | Injection Prevention | 100% of SQL queries must use parameterized PDO prepared statements. | Code review & schema audits |
| **NFR-SEC-03** | Cross-Site Scripting | All user-supplied HTML outputs must be sanitized via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. | Automated test vectors |
| **NFR-SEC-04** | Insecure Direct Object Refs | All vehicle/service data access must verify `WHERE user_id = ?` matching session ID. | IDOR penetration test scenarios |
| **NFR-PERF-01**| Response Latency | Server-side execution and page delivery must complete in <= 200 ms on primary environment. | Benchmark profiling |
| **NFR-PERF-02**| WebSocket Latency | Push notification broadcast delivery to connected clients must occur in <= 100 ms. | WebSocket CLI test client |
| **NFR-REL-01** | Database Reliability | Relational constraints and foreign keys with transactions must guarantee zero orphaned records. | MySQL schema integrity checks |
| **NFR-REL-02** | Event Delivery Fallback | If direct TCP WebSocket socket is blocked, events must persist to MySQL `realtime_events` queue. | `WebSocketHelperTest` verification |
| **NFR-COMP-01**| PHP Runtime Compatibility | Code must execute identically on PHP 8.0, 8.1, 8.2, 8.3, 8.4, and 8.5 without syntax warnings. | GitHub Actions CI matrix |
| **NFR-MAIN-01**| Zero Runtime Dependencies | Core runtime must function natively without mandatory Composer production packages. | Dependency tree inspection |

