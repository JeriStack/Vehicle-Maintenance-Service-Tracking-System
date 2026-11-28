# Component Responsibilities & Single Responsibility Matrix
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ARC-SRP-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Application Architecture (`VMS-DOC-ARC-APP-001`)  
**Status:** Approved Architecture Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Single Responsibility Principle (SRP) Guidelines

To prevent code bloat, tight coupling, and controller sprawl ("fat controllers"), every architectural component in the Vehicle Maintenance Service Tracking System is assigned strict, bounded responsibilities.

---

## 2. Component Responsibility Catalogue

### 2.1 Action Controllers (`actions/*.php`, `admin/actions/*.php`)
- **Primary Responsibility:** Orchestrate HTTP request handling, input extraction, and response dispatch.
- **Allowed:**
  - Enforce HTTP method (`POST` check).
  - Verify CSRF tokens (`CsrfHelper::validateToken()`).
  - Extract and sanitize input parameters from `$_POST`.
  - Dispatch parameters to domain Validators.
  - Call domain Services to execute business operations.
  - Redirect with session flash messages OR return JSON responses.
- **Strictly Prohibited:**
  - Writing raw SQL queries directly in controllers.
  - Implementing complex business calculation formulas.
  - Direct database mutation without domain service validation.

### 2.2 Security Middleware & Auth Guards (`helpers/AuthHelper.php`)
- **Primary Responsibility:** Gatekeep execution boundaries and enforce Role-Based Access Control.
- **Allowed:**
  - Verify session existence and validity (`requireAuth()`).
  - Enforce role requirements (`requireRole('admin' | 'user')`).
  - Enforce guest-only access for login/register pages (`guestOnly()`).
  - Regenerate session identifiers on authentication transitions.
- **Strictly Prohibited:**
  - Altering application state or modifying business records.
  - Emitting HTML layout content directly.

### 2.3 Input Validators (`helpers/ValidationHelper.php`)
- **Primary Responsibility:** Verify data format, constraints, and sanity before processing.
- **Allowed:**
  - Validate field types (email, integer, date `YYYY-MM-DD`, currency).
  - Check string length limits, VIN format (17 chars regex), plate formatting.
  - Return structured error arrays (`['field' => ['Error message']]`).
- **Strictly Prohibited:**
  - Executing state mutations or database writes.
  - Reading or writing session data.

### 2.4 Domain Services & Calculation Engines (`helpers/MaintenanceCalculator.php`)
- **Primary Responsibility:** Execute business rules, calculation algorithms, and multi-entity workflows.
- **Allowed:**
  - Calculate dual-criteria maintenance status ("whichever comes first").
  - Coordinate atomic mileage advancement across vehicles and mileage logs.
  - Dispatch WebSocket event payloads and SMTP emails.
  - Manage database transactions (`BEGIN`, `COMMIT`, `ROLLBACK`).
- **Strictly Prohibited:**
  - Reading raw `$_POST` or `$_GET` global variables directly.
  - Formatting HTML views or echoing output.

### 2.5 Data Persistence Repositories & Helpers (`helpers/DatabaseHelper.php`)
- **Primary Responsibility:** Encapsulate database I/O and maintain query integrity.
- **Allowed:**
  - Manage singleton PDO database connection.
  - Execute prepared SQL statements with parameter binding.
  - Enforce owner-scoped query constraints (`WHERE user_id = :uid`).
  - Return raw associative arrays or domain objects.
- **Strictly Prohibited:**
  - Evaluating business rules or authorization decisions.
  - Echoing output or manipulating HTTP response headers.

### 2.6 Presentation Views & Layouts (`includes/`, `vehicles/*.php`, `admin/*.php`)
- **Primary Responsibility:** Render data into accessible, responsive HTML markup.
- **Allowed:**
  - Render pre-calculated variables into HTML templates.
  - Escape all dynamic strings with `htmlspecialchars()`.
  - Include shared layouts (`header.php`, `sidebar.php`, `footer.php`).
- **Strictly Prohibited:**
  - Executing `INSERT`, `UPDATE`, or `DELETE` SQL queries.
  - Generating random security tokens or altering session roles.

### 2.7 Background Daemons & CLI Runners (`reminders/`, `websocket/`)
- **Primary Responsibility:** Asynchronous processing, real-time push, and scheduled sweeps.
- **Allowed:**
  - WebSocket Server: Maintain client connection registry, broadcast event frames.
  - CLI Scheduler: Sweep maintenance targets, evaluate due thresholds, generate notification records.
  - Acquire non-blocking file locks (`flock`) to prevent overlapping executions.
- **Strictly Prohibited:**
  - Serving interactive web pages to browser visitors.
  - Allowing web server invocations (`php_sapi_name() !== 'cli'` terminates immediately).

