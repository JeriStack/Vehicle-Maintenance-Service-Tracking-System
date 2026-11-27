# Acceptance Criteria Specification (Given / When / Then)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-AC-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Journey A: Vehicle Owner Registration & Authentication

### Scenario AC-AUTH-01: Successful User Registration
* **Given** a guest user navigates to `/auth/register.php`,
* **When** they submit valid registration details (full name: "Juan Dela Cruz", email: "juan@example.com", password: "Password123!", phone: "09171234567"),
* **Then** the system normalizes the email to lowercase, hashes the password using bcrypt, inserts the user with `role = 'user'`, and displays a success message on `/auth/login.php`.

### Scenario AC-AUTH-02: Registration with Duplicate Email
* **Given** a user account with email `juan@example.com` already exists,
* **When** a guest attempts to register with `Juan@Example.COM`,
* **Then** the registration is rejected, the transaction rolls back, and the form displays: *"Email address is already registered."*

### Scenario AC-AUTH-03: Public Role-Escalation Tampering Attempt
* **Given** a guest submits the registration form with an injected parameter `role=admin`,
* **When** the server processes the request in `actions/register_action.php`,
* **Then** the injected role parameter is ignored, the account is created with `role = 'user'`, and an audit log warning is recorded.

### Scenario AC-AUTH-04: Successful Login & Session Regeneration
* **Given** an active user account exists with password "Password123!",
* **When** the user logs in with valid credentials,
* **Then** the session ID is regenerated (`session_regenerate_id(true)`), `$_SESSION['user_id']` is populated, and the user is redirected to `/dashboard/index.php`.

### Scenario AC-AUTH-05: Failed Login with Invalid Credentials
* **Given** a user enters an unverified password or non-existent email,
* **When** they submit the login form,
* **Then** the login fails, a generic error *"Invalid email address or password"* is displayed, and account existence is not disclosed.

### Scenario AC-AUTH-06: Deactivated Account Login Blocked
* **Given** an account has its status set to `'inactive'`,
* **When** the user attempts to log in with correct credentials,
* **Then** authentication is blocked, the session is destroyed, and the user receives: *"Your account has been deactivated. Please contact an administrator."*

### Scenario AC-AUTH-07: Secure Logout & Token Destruction
* **Given** an authenticated user with an active remember-me cookie,
* **When** they click "Logout" (`/auth/logout.php`),
* **Then** the session is destroyed, the cookie is expired (`time() - 3600`), the token is removed from `remember_tokens`, and the browser redirects to `/auth/login.php`.

---

## 2. Journey B: Vehicle Management & Garage Operations

### Scenario AC-VEH-01: Add New Vehicle to Personal Garage
* **Given** an authenticated vehicle owner on `/vehicles/create.php`,
* **When** they submit valid details (Plate: "ABC-1234", Type: "Sedan", Make: "Toyota", Model: "Vios", Year: 2022, Mileage: 45000, Expiry: "2027-10-15"),
* **Then** a vehicle record is inserted with `user_id = $_SESSION['user_id']`, plate normalized to uppercase, and the user is redirected to `/vehicles/index.php` with a success alert.

### Scenario AC-VEH-02: Prevent Duplicate Plate for the Same Owner
* **Given** an owner already has an active vehicle with plate "ABC-1234",
* **When** they attempt to register another vehicle with plate "abc 1234",
* **Then** the submission is rejected with: *"You have already registered a vehicle with this plate number."*

### Scenario AC-VEH-03: Allow Identical Plate for Different Owners
* **Given** User 1 owns vehicle with plate "ABC-1234",
* **When** User 2 registers a vehicle with plate "ABC-1234",
* **Then** the registration succeeds because plate uniqueness is scoped to the individual owner.

### Scenario AC-VEH-04: Reject Out-of-Bounds Model Year
* **Given** an owner enters a model year of `1880` or `2040`,
* **When** they submit the vehicle creation form,
* **Then** the form returns an error: *"Vehicle year must be between 1886 and next year."*

### Scenario AC-VEH-05: Reject Odometer Regression on Edit
* **Given** a vehicle currently has an odometer reading of `50,000 km`,
* **When** the owner attempts to edit the vehicle and input `42,000 km`,
* **Then** the update is rejected with: *"Odometer reading cannot be less than the prior reading (50,000 km)."*

### Scenario AC-VEH-06: Archive Vehicle (Soft-Delete)
* **Given** an owner requests to delete a vehicle with 5 associated service records,
* **When** the action processes `action=delete` in `actions/vehicle_action.php`,
* **Then** the vehicle's `status` is updated to `'archived'`, the vehicle disappears from active garage views, and all 5 historical service records remain intact in the database.

### Scenario AC-VEH-07: Insecure Direct Object Reference (IDOR) Blocked
* **Given** User A owns vehicle ID `10` and User B owns vehicle ID `20`,
* **When** User A attempts to view or edit `/vehicles/edit.php?id=20` or submit POST for vehicle `20`,
* **Then** the request is blocked, `WHERE id = ? AND user_id = ?` returns false, and User A is redirected with HTTP 403 / *"Permission denied."*

---

## 3. Journey C: Service Records & Maintenance Logging

### Scenario AC-SRV-01: Log Completed Service with Monotonic Odometer
* **Given** an owner's vehicle currently has an odometer of `45,000 km`,
* **When** they log an "Oil Change" service performed at `48,500 km` for cost `₱2,500.00`,
* **Then** the service record is inserted with `status = 'Completed'`, and the vehicle's master odometer is automatically advanced to `48,500 km`.

### Scenario AC-SRV-02: Reject Future Completed Service Date
* **Given** the current date is `2026-11-05`,
* **When** an owner attempts to log a completed service with date `2026-11-20`,
* **Then** the submission is rejected: *"Completed service date cannot be in the future."*

### Scenario AC-SRV-03: Monetary Decimal Cost Formatting
* **Given** an owner inputs a service cost of `₱3,450.75`,
* **When** the record is stored and retrieved,
* **Then** it is stored in `DECIMAL(10,2)` without floating-point distortion and displayed formatted with the currency symbol `₱3,450.75`.

### Scenario AC-SRV-04: Void Service Record (Audit Trail)
* **Given** an erroneous service record exists for cost `₱5,000.00`,
* **When** the owner clicks "Void Record" and provides the reason *"Duplicate invoice logged in error"*,
* **Then** the service record status is changed to `'Voided'`, it is excluded from lifetime maintenance expenditure calculations, and an audit log entry is preserved.

---

## 4. Journey D: Maintenance Status Calculation Engine

### Scenario AC-MTN-01: Overdue Date Takes Precedence
* **Given** a vehicle has a scheduled service with target date `5 days ago` and target mileage `2,000 km in the future`,
* **When** the maintenance calculation engine executes,
* **Then** the returned status is **`OVERDUE`** because the date threshold has expired.

### Scenario AC-MTN-02: Overdue Mileage Takes Precedence
* **Given** a vehicle has a scheduled service with target date `30 days in the future` and target mileage `50,000 km` (while current odometer is `50,200 km`),
* **When** the maintenance calculation engine executes,
* **Then** the returned status is **`OVERDUE`** because the mileage threshold has been exceeded.

### Scenario AC-MTN-03: Due Today Date
* **Given** a scheduled service has target date equal to current calendar date (`CURRENT_DATE`),
* **When** the engine calculates status,
* **Then** the returned status is **`DUE`**.

### Scenario AC-MTN-04: Exact Distance Reached
* **Given** a scheduled service has target mileage `60,000 km` and current odometer is exactly `60,000 km`,
* **When** the engine calculates status,
* **Then** the returned status is **`DUE`**.

### Scenario AC-MTN-05: Upcoming Status Within Lead Window
* **Given** an owner has a configured reminder lead window of `14 days`,
* **When** a service is scheduled `10 days in the future`,
* **Then** the returned status is **`UPCOMING`**.

---

## 5. Journey E: Notifications & Real-Time Alerting

### Scenario AC-NTF-01: Idempotent Reminder Deduplication
* **Given** an upcoming service reminder was generated today for Vehicle A,
* **When** the background CLI cron script (`bin/process_reminders.php`) executes a second time,
* **Then** the unique `dedupe_key` prevents a duplicate row from being inserted into `notifications`.

### Scenario AC-NTF-02: Registration Expiring Alert Triggered
* **Given** a vehicle's registration expires in `15 days`,
* **When** the daily reminder processor executes,
* **Then** a notification is created with type `REMINDER` and message *"Registration for Toyota Vios (ABC-1234) expires in 15 days."*, and a real-time WebSocket toast is broadcast.

### Scenario AC-NTF-03: User Disables Notifications
* **Given** an owner disables `maintenance_reminder = 0` in `notification_settings`,
* **When** the reminder processor checks their upcoming service,
* **Then** zero notification records and zero WebSocket alerts are generated for that user.

---

## 6. Journey F: System Administration & Governance

### Scenario AC-ADM-01: Search & Filter User Accounts
* **Given** an authenticated administrator on `/admin/users.php`,
* **When** they enter "Juan" in the search box,
* **Then** the table returns all accounts matching "Juan" in their name or email address.

### Scenario AC-ADM-02: Account Deactivation Blocks User
* **Given** an administrator toggles an account status from `active` to `inactive`,
* **When** the targeted user attempts to browse the platform or log in,
* **Then** they are blocked with HTTP 403 / Account Deactivated, and the action is recorded in `audit_logs`.

### Scenario AC-ADM-03: Last-Administrator Demotion Blocked
* **Given** the system has only 1 active administrator account remaining,
* **When** an administrator attempts to change that account's role to `user`,
* **Then** the action is blocked: *"Cannot demote the last administrator in the system."*, and the role remains `admin`.

### Scenario AC-ADM-04: Administrator Self-Demotion Blocked
* **Given** an administrator attempts to change their own role on `admin/user_view.php`,
* **When** the POST request is submitted,
* **Then** the server rejects the request: *"You cannot change your own role."*

### Scenario AC-ADM-05: Owner Privacy Boundary Enforced
* **Given** an administrator attempts to browse `/vehicles/view.php?id=10` belonging to a vehicle owner,
* **When** the request is made,
* **Then** `requireUser()` blocks the administrator with HTTP 403 Forbidden, protecting private repair histories.

---

## 7. Journey G: Security & Negative Scenarios

### Scenario AC-SEC-01: SQL Injection Defense
* **Given** an attacker submits `' OR 1=1 --` into the vehicle search input,
* **When** the query executes via PDO prepared statements,
* **Then** the input is treated as a literal search string; no unauthorized records are returned.

### Scenario AC-SEC-02: Cross-Site Scripting (XSS) Defense
* **Given** an attacker submits `<script>alert('XSS')</script>` in the service remarks field,
* **When** the service record is displayed on `/services/view.php`,
* **Then** `htmlspecialchars()` escapes the characters to `&lt;script&gt;`, preventing script execution.

### Scenario AC-SEC-03: Cross-Site Request Forgery (CSRF) Defense
* **Given** a malicious external site attempts to submit a POST request to `/actions/vehicle_action.php`,
* **When** the request lacks a valid matching `$_POST['csrf_token']`,
* **Then** the server aborts the request with: *"Invalid request or session expired."*

