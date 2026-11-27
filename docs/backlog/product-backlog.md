# Product Backlog
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-PB-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Backlog Estimation Scale

Story points are estimated using the standard **Fibonacci scale** reflecting relative complexity, uncertainty, and effort:
* **1 Point:** Trivial modification (e.g. static template text update, simple regex constraint).
* **2 Points:** Simple task with well-understood requirements (e.g. basic CRUD action, straightforward view query).
* **3 Points:** Moderate task involving validation, form handling, and database interaction.
* **5 Points:** Complex story requiring multiple layers (e.g. backend action, transactional database logic, UI feedback, and automated tests).
* **8 Points:** Highly complex component with algorithmic logic or security implications (e.g. split-token authentication, dual-criteria status calculation).
* **13 Points:** Architectural epic component requiring decomposition before active sprint inclusion.

---

## 2. Product Backlog Items (PBI)

### Epic: `EP-AUTH` (Authentication & Account Security)

#### `US-AUTH-01`: User Registration
* **Description:** As a guest, I want to create a vehicle owner account so that I can catalog my vehicles and track maintenance.
* **Requirements:** `AUTH-001`, `AUTH-004`, `AUTH-009` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 1 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-AUTH-01`, `AC-AUTH-02`, `AC-AUTH-03` | **Dependencies:** None
* **Actionable Tasks:**
  * `TASK-AUTH-01-BE`: Backend validation, email normalization, bcrypt hashing in `actions/register_action.php`.
  * `TASK-AUTH-01-FE`: Responsive Bootstrap 5 glassmorphism form in `auth/register.php`.
  * `TASK-AUTH-01-TEST`: Automated test vectors in `tests/Unit/ValidationTest.php`.

#### `US-AUTH-02`: Secure Login & Session Establishment
* **Description:** As a registered user, I want to log in securely so that I can access my protected vehicle data.
* **Requirements:** `AUTH-002`, `AUTH-008` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 1 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-AUTH-04`, `AC-AUTH-05`, `AC-AUTH-06` | **Dependencies:** `US-AUTH-01`
* **Actionable Tasks:**
  * `TASK-AUTH-02-BE`: Credential verification with `password_verify()`, session regeneration in `actions/login_action.php`.
  * `TASK-AUTH-02-FE`: Glassmorphism login UI matching design reference with autofill styling in `auth/login.php`.
  * `TASK-AUTH-02-TEST`: Unit tests in `tests/Unit/AuthHelperTest.php`.

#### `US-AUTH-03`: Secure Logout & Token Revocation
* **Description:** As an authenticated user, I want to log out so that other users on the device cannot access my account.
* **Requirements:** `AUTH-003` | **Priority:** P0 (MVP) | **Estimate:** 2 pts | **Sprint:** Sprint 1 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-AUTH-07` | **Dependencies:** `US-AUTH-02`
* **Actionable Tasks:**
  * `TASK-AUTH-03-BE`: Session destruction, cookie clearance, and token removal in `auth/logout.php`.

#### `US-AUTH-04`: Remember-Me Persistent Login
* **Description:** As a user, I want the system to remember my login on trusted devices so that I don't have to re-enter credentials every time.
* **Requirements:** `AUTH-005` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 1 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-AUTH-04` | **Dependencies:** `US-AUTH-02`
* **Actionable Tasks:**
  * `TASK-AUTH-04-DB`: Schema migration `user_remember_tokens` table.
  * `TASK-AUTH-04-BE`: Split-token generation, validator rotation, and auto-login logic in `includes/auth.php`.
  * `TASK-AUTH-04-TEST`: Automated unit tests in `tests/Unit/RememberMeTest.php`.

#### `US-AUTH-05`: Self-Service Password Reset
* **Description:** As a user who forgot my password, I want to receive a secure reset link so that I can regain account access.
* **Requirements:** `AUTH-006` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 1 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-AUTH-01` | **Dependencies:** `US-AUTH-02`
* **Actionable Tasks:**
  * `TASK-AUTH-05-BE`: 60-minute SHA-256 token generation and password updating in `actions/forgot_password_action.php`.
  * `TASK-AUTH-05-MAIL`: Native RFC 5321 socket client in `helpers/mail_helper.php` with offline log fallback.
  * `TASK-AUTH-05-TEST`: Unit tests in `tests/Unit/PasswordResetTest.php` and `MailHelperTest.php`.

#### `US-AUTH-06`: Role-Based Access Control (RBAC) Enforcement
* **Description:** As a system architect, I want centralized route guards so that unauthorized users cannot access restricted administrative or user endpoints.
* **Requirements:** `AUTH-007` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 1 | **Owner:** Lead Architect
* **Acceptance Criteria:** `AC-AUTH-03`, `AC-ADM-05` | **Dependencies:** `US-AUTH-02`
* **Actionable Tasks:**
  * `TASK-AUTH-06-BE`: Implementation of `requireLogin()`, `requireAdmin()`, `requireUser()`, `requireGuest()` in `includes/auth.php`.
  * `TASK-AUTH-06-TEST`: Automated RBAC state assertions in `tests/Unit/AuthHelperTest.php`.

---

### Epic: `EP-VEH` (Vehicle Garage Management)

#### `US-VEH-01`: Register Personal Vehicle
* **Description:** As a vehicle owner, I want to add a vehicle to my garage so that I can track its maintenance and registration.
* **Requirements:** `VEH-001`, `VEH-006` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 2 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-VEH-01`, `AC-VEH-02`, `AC-VEH-04` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-VEH-01-BE`: Plate canonicalization, year range validation, and PDO insertion in `actions/vehicle_action.php`.
  * `TASK-VEH-01-FE`: Creation form in `vehicles/create.php` with responsive validation styling.

#### `US-VEH-02`: View & Search Personal Garage
* **Description:** As a vehicle owner, I want to view my registered vehicles so that I can inspect their status and odometer readings.
* **Requirements:** `VEH-002`, `VEH-005` | **Priority:** P0 (MVP) | **Estimate:** 2 pts | **Sprint:** Sprint 2 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-VEH-01`, `AC-VEH-07` | **Dependencies:** `US-VEH-01`
* **Actionable Tasks:**
  * `TASK-VEH-02-FE`: Responsive garage card grid and search filters in `vehicles/index.php`.
  * `TASK-VEH-02-SEC`: Owner-scoped query enforcement (`WHERE user_id = ?`).

#### `US-VEH-03`: Update Vehicle Details & Odometer
* **Description:** As a vehicle owner, I want to update vehicle attributes and mileage so that records remain current.
* **Requirements:** `VEH-003` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 2 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-VEH-05`, `AC-VEH-07` | **Dependencies:** `US-VEH-01`
* **Actionable Tasks:**
  * `TASK-VEH-03-BE`: Validation blocking odometer regression below prior reading in `actions/vehicle_action.php`.
  * `TASK-VEH-03-FE`: Edit view in `vehicles/edit.php`.

#### `US-VEH-04`: Archive Vehicle (Soft-Delete)
* **Description:** As a vehicle owner, I want to remove a vehicle from my active garage without deleting historical service invoices.
* **Requirements:** `VEH-004` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 2 | **Owner:** Database Eng
* **Acceptance Criteria:** `AC-VEH-06` | **Dependencies:** `US-VEH-01`
* **Actionable Tasks:**
  * `TASK-VEH-04-BE`: Refactor delete action to `UPDATE vehicles SET status = 'archived'` in `actions/vehicle_action.php`.
  * `TASK-VEH-04-FE`: Filter active listings to exclude archived vehicles (`WHERE status != 'archived'`).

#### `US-VEH-05`: Odometer Audit History Log
* **Description:** As a vehicle owner, I want an auditable history of odometer readings so that I can verify mileage progression.
* **Requirements:** `VEH-007` | **Priority:** P1 (Rel 1.1) | **Estimate:** 3 pts | **Sprint:** Sprint 2 | **Owner:** Database Eng
* **Acceptance Criteria:** `AC-VEH-05` | **Dependencies:** `US-VEH-03`
* **Actionable Tasks:**
  * `TASK-VEH-05-DB`: Insert row into `vehicle_mileage_logs` upon vehicle creation or odometer edit.

---

### Epic: `EP-SRV` (Service Records & Maintenance History)

#### `US-SRV-01`: Log Completed Service Record
* **Description:** As a vehicle owner, I want to record completed maintenance with cost and provider details so that I have a verifiable service history.
* **Requirements:** `SRV-001`, `SRV-005`, `SRV-006`, `SRV-007` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 3 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-SRV-01`, `AC-SRV-02`, `AC-SRV-03` | **Dependencies:** `US-VEH-01`
* **Actionable Tasks:**
  * `TASK-SRV-01-BE`: Transactional insertion in `actions/service_action.php` with monotonic vehicle odometer update.
  * `TASK-SRV-01-FE`: Service logging form with date, cost (₱), provider, parts in `services/create.php`.

#### `US-SRV-02`: Filterable Service History Log
* **Description:** As a vehicle owner, I want to search and filter my service history so that I can locate specific maintenance events.
* **Requirements:** `SRV-002` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 3 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-SRV-01` | **Dependencies:** `US-SRV-01`
* **Actionable Tasks:**
  * `TASK-SRV-02-FE`: Table view with vehicle, service type, and date range filters in `services/index.php`.

#### `US-SRV-03`: Edit Completed Service Record
* **Description:** As a vehicle owner, I want to update remarks or costs on a logged service record so that I can correct minor typographical errors.
* **Requirements:** `SRV-003` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 3 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-SRV-01` | **Dependencies:** `US-SRV-01`
* **Actionable Tasks:**
  * `TASK-SRV-03-BE`: Owner verification and update handling in `actions/service_action.php`.

#### `US-SRV-04`: Void Erroneous Service Record
* **Description:** As a vehicle owner, I want to void an invalid service entry with a mandatory reason so that financial totals are corrected without destroying audit history.
* **Requirements:** `SRV-004` | **Priority:** P1 (Rel 1.1) | **Estimate:** 3 pts | **Sprint:** Sprint 3 | **Owner:** Database Eng
* **Acceptance Criteria:** `AC-SRV-04` | **Dependencies:** `US-SRV-01`
* **Actionable Tasks:**
  * `TASK-SRV-04-BE`: Update status to `'Voided'`, capture void reason, and exclude from expenditure queries.

---

### Epic: `EP-MTN` (Maintenance Tracking & Scheduling)

#### `US-MTN-01`: Dual-Criteria Maintenance Scheduling
* **Description:** As a vehicle owner, I want to schedule future maintenance by target date, target mileage, or both so that the system tracks my next service.
* **Requirements:** `MTN-001` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 4 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-MTN-01` | **Dependencies:** `US-SRV-01`
* **Actionable Tasks:**
  * `TASK-MTN-01-BE`: Form validation ensuring at least one valid trigger in `services/create.php`.

#### `US-MTN-02`: Dynamic Status Calculation Engine
* **Description:** As a system architect, I want an isolated calculation engine implementing the "whichever comes first" rule so that maintenance status is determined accurately.
* **Requirements:** `MTN-002` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 4 | **Owner:** Lead Architect
* **Acceptance Criteria:** `AC-MTN-01` through `AC-MTN-05` | **Dependencies:** `US-MTN-01`
* **Actionable Tasks:**
  * `TASK-MTN-02-CORE`: Pure domain calculation engine in `helpers/maintenance_calculator.php`.
  * `TASK-MTN-02-TEST`: Comprehensive test suite in `tests/Unit/MaintenanceCalculatorTest.php` (8 tests passing).

#### `US-MTN-03`: Dedicated Status Monitoring Views
* **Description:** As a vehicle owner, I want categorized views for upcoming, due, and overdue maintenance so that I can prioritize urgent repairs.
* **Requirements:** `MTN-003`, `MTN-004`, `MTN-005` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 4 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-MTN-01` through `AC-MTN-05` | **Dependencies:** `US-MTN-02`
* **Actionable Tasks:**
  * `TASK-MTN-03-FE`: Visual badge cards in `maintenance/upcoming.php`, `due.php`, and `overdue.php`.

#### `US-MTN-04`: Dedicated Maintenance Schedules Entity
* **Description:** As a software engineer, I want future maintenance schedules decoupled from past completed service records into `maintenance_schedules` so that scheduling is independent of invoices.
* **Requirements:** `MTN-006` | **Priority:** P1 (Rel 1.1) | **Estimate:** 5 pts | **Sprint:** Sprint 4 | **Owner:** Database Eng
* **Acceptance Criteria:** `AC-MTN-01` | **Dependencies:** `US-MTN-02`
* **Actionable Tasks:**
  * `TASK-MTN-04-DB`: Apply migration `database/migrations/align_with_blueprint_schema.sql` (`maintenance_schedules`).

---

### Epic: `EP-NTF` (Notifications & Real-Time Alerting)

#### `US-NTF-01`: In-App Notification Center
* **Description:** As a vehicle owner, I want a notification inbox with unread counts so that I can review recent maintenance alerts.
* **Requirements:** `NTF-001`, `NTF-006` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 5 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-NTF-02` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-NTF-01-FE`: Notification feed and mark-read actions in `notifications/index.php` and `navbar.php`.

#### `US-NTF-02`: Automated Reminders Processor
* **Description:** As a system administrator, I want a background job that evaluates maintenance and registration dates so that alerts are generated automatically.
* **Requirements:** `NTF-002`, `NTF-003` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 5 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-NTF-02`, `AC-NTF-03` | **Dependencies:** `US-MTN-02`
* **Actionable Tasks:**
  * `TASK-NTF-02-CLI`: Headless CLI runner `bin/process_reminders.php` with concurrency file locking (`flock`).

#### `US-NTF-03`: Idempotent Reminder Deduplication
* **Description:** As a vehicle owner, I want the system to avoid spamming duplicate notifications so that my inbox remains clean.
* **Requirements:** `NTF-004` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 5 | **Owner:** Database Eng
* **Acceptance Criteria:** `AC-NTF-01` | **Dependencies:** `US-NTF-02`
* **Actionable Tasks:**
  * `TASK-NTF-03-DB`: Deterministic deduplication key hashing and 7-day suppression in `helpers/notification_helper.php`.

#### `US-NTF-04`: Real-Time WebSocket Alerts
* **Description:** As an active user, I want instant desktop notification toasts and audio chimes when an event occurs so that I am notified immediately.
* **Requirements:** `NTF-005` | **Priority:** P0 (MVP) | **Estimate:** 8 pts | **Sprint:** Sprint 5 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-NTF-02` | **Dependencies:** `US-NTF-01`
* **Actionable Tasks:**
  * `TASK-NTF-04-WS`: Native RFC 6455 socket daemon `websocket/server.php` and dual-mode broadcaster `helpers/websocket_helper.php`.
  * `TASK-NTF-04-JS`: Auto-reconnecting client engine `assets/js/notifications.js` with Web Audio API chime.
  * `TASK-NTF-04-TEST`: WebSocket framing unit tests in `tests/Unit/WebSocketFrameTest.php`.

#### `US-NTF-05`: Automated SMTP Email Alerting
* **Description:** As a vehicle owner, I want email notifications for critical overdue maintenance so that I am alerted even when not logged in.
* **Requirements:** `NTF-007` | **Priority:** P1 (Rel 1.1) | **Estimate:** 3 pts | **Sprint:** Sprint 5 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-NTF-02` | **Dependencies:** `US-NTF-02`
* **Actionable Tasks:**
  * `TASK-NTF-05-MAIL`: Hook `MailHelper::send()` into the reminder processor when `email_notifications = 1`.

---

### Epic: `EP-DASH` (Dashboards & Summaries)

#### `US-DASH-01`: Vehicle Owner Personal Dashboard
* **Description:** As a vehicle owner, I want an executive summary of my garage, expenses, and upcoming maintenance so that I have immediate visibility.
* **Requirements:** `DASH-001`, `DASH-003` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 6 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-DASH-01` | **Dependencies:** `US-VEH-02`, `US-SRV-02`
* **Actionable Tasks:**
  * `TASK-DASH-01-FE`: Metric cards, quick action links, and recent services table in `dashboard/index.php`.

#### `US-DASH-02`: Real-Time Status Counters
* **Description:** As a vehicle owner, I want dashboard counters to update automatically when a service is logged so that I don't need to refresh the page.
* **Requirements:** `DASH-002` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 6 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-DASH-01` | **Dependencies:** `US-NTF-04`
* **Actionable Tasks:**
  * `TASK-DASH-02-JS`: Dynamic DOM counter updates triggered by WebSocket broadcast events.

#### `US-DASH-03`: Administrator Platform Overview Dashboard
* **Description:** As an administrator, I want to view platform-wide totals so that I can monitor system adoption and health.
* **Requirements:** `DASH-004` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 6 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-ADM-01` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-DASH-03-FE`: Platform metric cards (Total Users, Active Vehicles, Completed Services) in `admin/dashboard.php`.

---

### Epic: `EP-RPT` (Reports, Analytics & Data Export)

#### `US-RPT-01`: Maintenance Summary Report
* **Description:** As a vehicle owner, I want aggregated maintenance metrics per vehicle so that I can see total costs and completed tasks.
* **Requirements:** `RPT-001`, `RPT-004` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-RPT-01` | **Dependencies:** `US-SRV-01`
* **Actionable Tasks:**
  * `TASK-RPT-01-BE`: Aggregation SQL queries with `SUM` and `COUNT` in `reports/maintenance.php`.

#### `US-RPT-02`: Chronological Service History Log
* **Description:** As a vehicle owner, I want a complete log of all past service records so that I can review maintenance history.
* **Requirements:** `RPT-002` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-RPT-01` | **Dependencies:** `US-SRV-02`
* **Actionable Tasks:**
  * `TASK-RPT-02-FE`: Full-width filterable historical log in `reports/service_history.php`.

#### `US-RPT-03`: Monthly & Yearly Expenditure Analytics
* **Description:** As a vehicle owner, I want visual spending charts so that I can understand my monthly and annual maintenance trends.
* **Requirements:** `RPT-003` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 7 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-RPT-01` | **Dependencies:** `US-SRV-01`
* **Actionable Tasks:**
  * `TASK-RPT-03-FE`: Chart.js bar and line charts in `reports/costs.php`, `monthly.php`, and `yearly.php`.

#### `US-RPT-04`: RFC 4180 CSV Data Export
* **Description:** As a vehicle owner, I want to download my maintenance logs as a CSV spreadsheet so that I can analyze them in Excel or Google Sheets.
* **Requirements:** `RPT-005` | **Priority:** P1 (Rel 1.1) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-RPT-01` | **Dependencies:** `US-RPT-02`
* **Actionable Tasks:**
  * `TASK-RPT-04-BE`: Output streaming via `php://output` and `fputcsv()` in `reports/export_csv.php`.

---

### Epic: `EP-SET` (Settings & Preferences)

#### `US-SET-01`: User Profile Management
* **Description:** As a user, I want to update my profile details so that my contact information is accurate.
* **Requirements:** `SET-001` | **Priority:** P0 (MVP) | **Estimate:** 2 pts | **Sprint:** Sprint 5 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-SET-01` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-SET-01-BE`: Form validation and update handler in `settings/profile.php` and `actions/profile_action.php`.

#### `US-SET-02`: Account Password Change
* **Description:** As a user, I want to change my account password so that I can maintain security.
* **Requirements:** `SET-002` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 5 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-SET-01` | **Dependencies:** `US-AUTH-02`
* **Actionable Tasks:**
  * `TASK-SET-02-BE`: Current password verification with `password_verify()` and re-hashing in `settings/password.php`.

#### `US-SET-03`: Notification Preferences Configuration
* **Description:** As a vehicle owner, I want to configure reminder lead days and toggles so that I receive notifications on my schedule.
* **Requirements:** `SET-003` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 5 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-NTF-03` | **Dependencies:** `US-NTF-01`
* **Actionable Tasks:**
  * `TASK-SET-03-FE`: Toggle inputs and lead-days selector in `settings/notifications.php`.

#### `US-SET-04`: Default Vehicle Servicing Preferences
* **Description:** As a vehicle owner, I want to establish default servicing intervals so that new service forms pre-populate automatically.
* **Requirements:** `SET-004` | **Priority:** P1 (Rel 1.1) | **Estimate:** 2 pts | **Sprint:** Sprint 5 | **Owner:** Database Eng
* **Acceptance Criteria:** `AC-SET-01` | **Dependencies:** `US-VEH-01`
* **Actionable Tasks:**
  * `TASK-SET-04-BE`: Preferences storage in `vehicle_preferences` and form pre-fill logic.

---

### Epic: `EP-ADM` (System Administration & Governance)

#### `US-ADM-01`: User Account Directory & Search
* **Description:** As an administrator, I want to search and view user accounts so that I can manage platform users.
* **Requirements:** `ADM-001` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-ADM-01` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-ADM-01-FE`: Searchable user table with pagination and role badges in `admin/users.php`.

#### `US-ADM-02`: Account Activation / Deactivation
* **Description:** As an administrator, I want to toggle an account's active status so that I can block abusive or compromised accounts.
* **Requirements:** `ADM-002` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-ADM-02` | **Dependencies:** `US-ADM-01`
* **Actionable Tasks:**
  * `TASK-ADM-02-BE`: Add status toggle POST handler to `actions/admin_user_action.php` and audit log entry.
  * `TASK-ADM-02-FE`: Add Activate/Deactivate action button on `admin/user_view.php`.

#### `US-ADM-03`: Role Management with Last-Admin Protection
* **Description:** As an administrator, I want to promote or demote accounts while being prevented from demoting the last active administrator.
* **Requirements:** `ADM-003`, `ADM-004` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 7 | **Owner:** Security Review
* **Acceptance Criteria:** `AC-ADM-03`, `AC-ADM-04` | **Dependencies:** `US-ADM-01`
* **Actionable Tasks:**
  * `TASK-ADM-03-BE`: Self-demotion and count check guards in `actions/admin_user_action.php`.
  * `TASK-ADM-03-TEST`: Automated test scenarios in test suite.

#### `US-ADM-04`: Global Maintenance Service Catalog
* **Description:** As an administrator, I want to manage standard global maintenance types so that vehicle owners choose from a curated service list.
* **Requirements:** `ADM-005` | **Priority:** P1 (Rel 1.1) | **Estimate:** 5 pts | **Sprint:** Sprint 7 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-ADM-01` | **Dependencies:** `US-ADM-01`
* **Actionable Tasks:**
  * `TASK-ADM-04-FE`: Catalog management UI in `admin/maintenance_types.php` supporting CRUD on `maintenance_types`.

#### `US-ADM-05`: Security Audit Log Viewer
* **Description:** As an administrator, I want to review timestamped audit logs so that I can inspect critical administrative and security events.
* **Requirements:** `ADM-006` | **Priority:** P0 (MVP) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Frontend Dev
* **Acceptance Criteria:** `AC-ADM-03` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-ADM-05-FE`: Audit log table with actor, action, table, and date filters in `admin/audit_logs.php`.

#### `US-ADM-06`: Owner Privacy Boundary Enforcement
* **Description:** As a system architect, I want to prevent administrators from inspecting private owner service records so that user privacy is preserved.
* **Requirements:** `ADM-007` | **Priority:** P0 (MVP) | **Estimate:** 5 pts | **Sprint:** Sprint 7 | **Owner:** Lead Architect
* **Acceptance Criteria:** `AC-ADM-05` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-ADM-06-BE`: Refactor `admin/vehicles.php` and `admin/services.php` to present platform aggregations rather than private owner service notes.

#### `US-ADM-07`: System-Wide Configuration Management
* **Description:** As an administrator, I want to manage platform settings so that I can configure application behavior.
* **Requirements:** `ADM-008` | **Priority:** P1 (Rel 1.1) | **Estimate:** 3 pts | **Sprint:** Sprint 7 | **Owner:** Backend Dev
* **Acceptance Criteria:** `AC-ADM-01` | **Dependencies:** `US-AUTH-06`
* **Actionable Tasks:**
  * `TASK-ADM-07-FE`: Settings portal in `admin/settings.php`.

---

## 3. Backlog Point Summary

* **Total Epics:** 9
* **Total User Stories:** 37
* **P0 (MVP) Stories:** 28 stories (105 Story Points)
* **P1 (Release 1.1) Stories:** 9 stories (30 Story Points)
* **Total Story Points:** **135 Points**

