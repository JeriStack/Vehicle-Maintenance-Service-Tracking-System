# Business Rules & Validation Catalogue
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-BRV-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Catalogue Overview

This catalogue defines the authoritative **Business Rules, Validation Constraints, Data Integrity Policies, and Security Invariants** governing the VMS platform. Each rule specifies its unique identifier, affected requirement, exact logic, failure behavior, and corresponding automated test reference.

---

## 2. Rule Catalogue

### 2.1 Authentication & Account Security Rules (`BR-AUTH`)

| Rule ID | Affected Req | Rule Title & Logic | Failure / Enforcement Behavior | Test Scenario Ref |
|---|---|---|---|---|
| **BR-AUTH-01** | AUTH-001 | **Email Normalization & Case-Insensitive Uniqueness:** Input emails must be trimmed and converted to lowercase (`strtolower(trim($email))`). Database column has a `UNIQUE` constraint. | Rejects duplicate emails with friendly error: *"Email address is already registered."* | `testValidEmailAddresses`, `testInvalidEmailAddresses` |
| **BR-AUTH-02** | AUTH-001 | **Public Registration Role Boundary:** Public user self-registration at `/auth/register.php` must strictly assign `role = 'user'` (`VEHICLE_OWNER`). No client-supplied role parameters are accepted. | Role injection attempts via POST tampering are ignored; record forced to `'user'`. | `TC-AUTH-05` |
| **BR-AUTH-03** | AUTH-002 | **Credential Verification & Generic Failure:** Passwords must be verified via `password_verify($password, $hash)`. Incorrect password or non-existent email must return identical generic error. | Displays: *"Invalid email address or password."* Prevents user enumeration. | `TC-AUTH-07` |
| **BR-AUTH-04** | AUTH-002 | **Account Status Check on Authentication:** Inactive/deactivated accounts (`status = 'inactive'`) cannot authenticate, even with valid credentials. | Rejects login with: *"Your account has been deactivated. Please contact an administrator."* | `TC-AUTH-08` |
| **BR-AUTH-05** | AUTH-008 | **Session Fixation Mitigation:** Session identifiers must be regenerated upon successful authentication and role switches (`session_regenerate_id(true)`). | Old session identifier is invalidated; new ID prevents session hijacking. | `TC-AUTH-09` |
| **BR-AUTH-06** | AUTH-003 | **Session & Cookie Invalidation on Logout:** Logout must unset all session variables, destroy the session, expire the session cookie, and delete persistent remember tokens from MySQL. | User redirected to `/auth/login.php`; subsequent requests require full re-authentication. | `TC-AUTH-10` |
| **BR-AUTH-07** | AUTH-005 | **Split-Token Persistent Login Invariant:** Remember-me authentication must utilize a 16-byte random selector and a SHA-256 hashed 32-byte validator. Validators must rotate upon each successful auto-login. | Stolen cookie replay attempts fail cryptographic comparison (`hash_equals()`). | `testTokenFormatAndEntropy`, `testHashEqualsValidation` |
| **BR-AUTH-08** | AUTH-006 | **Password Reset Token Expiry:** Password reset tokens expire exactly 60 minutes after generation. Tokens are stored as SHA-256 hashes in `password_resets`. | Expired tokens return: *"This reset link has expired. Please request a new one."* | `testPasswordResetExpiryWindow` |

---

### 2.2 Vehicle Validation Rules (`BR-VEH`)

| Rule ID | Affected Req | Rule Title & Logic | Failure / Enforcement Behavior | Test Scenario Ref |
|---|---|---|---|---|
| **BR-VEH-01** | VEH-006 | **Plate Number Normalization:** Plate input is trimmed, converted to uppercase, and stripped of non-alphanumeric separators for canonical validation: `preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($plate)))`. | Canonical string used for comparison; formatted plate stored for display. | `TC-VEH-06` |
| **BR-VEH-02** | VEH-006 | **Owner-Scoped Plate Uniqueness:** A normalized plate number must be unique within an individual owner's garage. Two different owners *may* legitimately register the same plate (e.g. across private fleets or jurisdictions). | Duplicate plate for the same owner is rejected: *"You have already registered a vehicle with this plate number."* | `TC-VEH-07` |
| **BR-VEH-03** | VEH-001 | **Vehicle Model Year Bounds:** Vehicle year must be a valid 4-digit integer satisfying: $1886 \le \text{Year} \le (\text{Current Year} + 1)$. | Out-of-bounds year rejected: *"Vehicle year must be between 1886 and next year."* | `TC-VEH-08` |
| **BR-VEH-04** | VEH-001 | **Non-Negative Odometer:** Vehicle odometer reading must be an integer $\ge 0$ expressed in kilometres (KM). | Negative values rejected: *"Current mileage cannot be negative."* | `testIsPositiveNumber`, `TC-VEH-09` |
| **BR-VEH-05** | VEH-003 | **Monotonic Odometer Invariant (No Silent Regression):** Updating a vehicle's odometer through edit forms cannot decrease below the latest accepted reading. | Regressive mileage rejected: *"Odometer reading cannot be less than the prior reading (%d km)."* | `TC-VEH-10` |
| **BR-VEH-06** | VEH-004 | **Vehicle Archival Policy (Soft-Delete):** Deleting a vehicle sets `status = 'archived'`. Associated service records, schedules, and audit logs are permanently preserved. | Vehicle excluded from active garage; past financial summaries remain intact. | `TC-VEH-04` |
| **BR-VEH-07** | VEH-005 | **Ownership Data Isolation (IDOR Guard):** All vehicle queries and mutations must enforce `WHERE id = ? AND user_id = ?` matching `$_SESSION['user_id']`. | Unauthorized access returns HTTP 403 / redirects with: *"Permission denied."* | `TC-VEH-11` |

---

### 2.3 Service Record & History Rules (`BR-SRV`)

| Rule ID | Affected Req | Rule Title & Logic | Failure / Enforcement Behavior | Test Scenario Ref |
|---|---|---|---|---|
| **BR-SRV-01** | SRV-001 | **Completed Service Date Invariant:** Completed service date cannot be in the future: $\text{Service Date} \le \text{Current Date}$ (evaluated in `Asia/Manila` time). | Future date rejected: *"Completed service date cannot be in the future."* | `TC-SRV-05` |
| **BR-SRV-02** | SRV-005 | **Odometer Advancement on Service Logging:** If service mileage > vehicle current mileage, vehicle's `current_mileage` is updated forward automatically within a database transaction. | Guarantees vehicle odometer advances as new service events are logged. | `TC-SRV-01` |
| **BR-SRV-03** | SRV-007 | **Monetary Decimal Precision:** Cost values must be non-negative decimals stored as `DECIMAL(12,2)`. No scientific float notation allowed. | Negative or invalid values rejected: *"Cost must be a valid positive amount."* | `TC-SRV-07` |
| **BR-SRV-04** | SRV-006 | **Descriptive Metadata Sanitization:** Mechanic/provider names and parts replaced are descriptive text fields; all outputs must be escaped with `htmlspecialchars()`. | Prevents Cross-Site Scripting (XSS) from unvalidated mechanic notes. | `testSanitizeOutput` |
| **BR-SRV-05** | SRV-004 | **Service Record Voiding Workflow:** Completed services cannot be silently deleted. Faulty records are voided with a mandatory reason; voided records are excluded from cost totals. | Record preserved in audit history with `status = 'Voided'`; expenditure totals updated. | `TC-SRV-04` |

---

### 2.4 Maintenance Status Calculation Rules (`BR-MTN`)

| Rule ID | Affected Req | Rule Title & Logic | Failure / Enforcement Behavior | Test Scenario Ref |
|---|---|---|---|---|
| **BR-MTN-01** | MTN-001 | **Mandatory Scheduling Trigger:** Every maintenance schedule must define at least one valid trigger: `next_service_date`, `next_service_mileage`, or both. | Form submission missing both triggers is rejected: *"Please provide a next service date or mileage limit."* | `TC-MTN-04` |
| **BR-MTN-02** | MTN-002 | **"Whichever Comes First" Dual-Criteria Precedence:** If both date and mileage triggers exist, they are evaluated independently. The most critical status takes absolute precedence. Precedence order: **`OVERDUE` > `DUE` > `UPCOMING` > `NOT_DUE`**. | If date is overdue but mileage is not reached, status is **`OVERDUE`**. If date is upcoming but mileage is reached today, status is **`DUE`**. | `testOverdueDateTakesPrecedence`, `testOverdueMileageTakesPrecedence` |
| **BR-MTN-03** | MTN-002 | **Date-Based Status Boundaries:**<br>• $\text{Target Date} < \text{Today} \implies \text{OVERDUE}$<br>• $\text{Target Date} = \text{Today} \implies \text{DUE}$<br>• $0 < (\text{Target Date} - \text{Today}) \le \text{Lead Days} \implies \text{UPCOMING}$<br>• $(\text{Target Date} - \text{Today}) > \text{Lead Days} \implies \text{NOT\_DUE}$. | Evaluated dynamically using `DateTime` in local application timezone (`Asia/Manila`). | `testDueTodayDate`, `testUpcomingWithinDateWindow` |
| **BR-MTN-04** | MTN-002 | **Mileage-Based Status Boundaries:**<br>• $\text{Current Mileage} > \text{Target Mileage} \implies \text{OVERDUE}$<br>• $\text{Current Mileage} = \text{Target Mileage} \implies \text{DUE}$<br>• $0 < (\text{Target Mileage} - \text{Current Mileage}) \le \text{Reminder Window} \implies \text{UPCOMING}$ (default 500–1,000 km)<br>• Remaining Mileage $> \text{Reminder Window} \implies \text{NOT\_DUE}$. | Evaluated against master vehicle odometer. | `testDueExactDistanceReached`, `testUpcomingWithinDistanceWindow` |
| **BR-MTN-05** | MTN-006 | **Schedule Superseding on Completion:** When a completed service record is recorded that fulfills a scheduled maintenance type, the corresponding open schedule is closed/superseded. | Prevents duplicate reminders for service tasks that have already been executed. | `TC-MTN-10` |

---

### 2.5 Registration Expiry & Notification Rules (`BR-NTF`)

| Rule ID | Affected Req | Rule Title & Logic | Failure / Enforcement Behavior | Test Scenario Ref |
|---|---|---|---|---|
| **BR-NTF-01** | NTF-003 | **Registration Expiry Evaluation:** Evaluated using local calendar dates:<br>• $\text{Expiry} < \text{Today} \implies \text{Expired (OVERDUE)}$<br>• $\text{Expiry} = \text{Today} \implies \text{Expires Today (DUE)}$<br>• $0 < (\text{Expiry} - \text{Today}) \le 30 \text{ Days} \implies \text{Expiring Soon (REMINDER)}$. | Alerts generated with title "Registration Expiring Soon" or "Registration Expired". | `TC-NTF-02` |
| **BR-NTF-02** | NTF-004 | **Idempotent Notification Deduplication:** Reminders generate a deterministic deduplication key (`dedupe_key = hash(user_id + vehicle_id + type + target_date)`). Notifications table enforces a 7-day suppression window. | Repeated background cron executions do not generate duplicate notification rows. | `TC-NTF-05` |
| **BR-NTF-03** | NTF-002 | **User Notification Preference Enforcement:** Notifications are generated only if the corresponding toggle in `notification_settings` is enabled (`maintenance_reminder`, `registration_reminder`, `overdue_reminder`). | If user disabled registration reminders, zero notifications or WebSocket pushes are created for registration events. | `TC-NTF-03` |

---

### 2.6 Administrator Privilege & Privacy Rules (`BR-ADM`)

| Rule ID | Affected Req | Rule Title & Logic | Failure / Enforcement Behavior | Test Scenario Ref |
|---|---|---|---|---|
| **BR-ADM-01** | ADM-004 | **Self-Demotion Safeguard:** An administrator cannot demote their own account to `user`: `if ($userId === currentUserId())`. | Rejection error: *"You cannot change your own role."* | `TC-ADM-08` |
| **BR-ADM-02** | ADM-004 | **Last-Administrator Protection Invariant:** An administrator cannot be demoted or deactivated if the system has only one active administrator remaining: `SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' <= 1`. | Rejection error: *"Cannot demote the last administrator in the system."* Protects against complete administrative lockout. | `TC-ADM-09` |
| **BR-ADM-03** | ADM-006 | **Mandatory Administrative Audit Logging:** All role changes, account status toggles, and catalog mutations must write an entry to `audit_logs` within the same database transaction. | Captures `user_id`, `action`, `table_name`, `record_id`, and `created_at`. | `TC-ADM-11` |
| **BR-ADM-04** | ADM-007 | **Owner Privacy Boundary Invariant:** Administrative roles grant platform management privileges, but **strictly prohibit** direct viewing or modifying of private vehicle service histories. | Administrative views query aggregated metrics; direct access to private owner records is rejected. | `TC-ADM-12` |

