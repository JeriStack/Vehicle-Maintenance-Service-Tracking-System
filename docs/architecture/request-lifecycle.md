# End-to-End Request Lifecycle Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ARC-LFC-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Application Architecture (`VMS-DOC-ARC-APP-001`)  
**Status:** Approved Architecture Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Validated Request Lifecycle Architecture

Every incoming HTTP request processed by the Vehicle Maintenance Service Tracking System traverses a standardized, deterministic execution pipeline:

```text
               [ 1. BROWSER INCOMING REQUEST ]
                             │ HTTP GET / POST
                             ▼
               [ 2. APACHE & .HTACCESS ROUTING ]
                             │ Matches RewriteRule / Direct Script
                             ▼
               [ 3. APPLICATION ENTRY POINT ]
               - Loads config/config.php, config/database.php
               - Starts secure session (session_start)
               - Sets UTF-8 encoding & security response headers
                             │
                             ▼
               [ 4. AUTHENTICATION MIDDLEWARE GUARD ]
               - Evaluates $_SESSION['user_id']
               - Checks split-token remember-me cookie if session absent
               - Redirects unauthenticated requests to /auth/login.php
                             │ Authenticated Session Confirmed
                             ▼
               [ 5. ROLE-BASED ACCESS CONTROL (RBAC) GUARD ]
               - Evaluates $_SESSION['role'] ('admin' vs 'user')
               - Blocks horizontal role crossing (403 Forbidden)
               - Enforces Admin Privacy Barrier
                             │ Role Authorized
                             ▼
               [ 6. CSRF & HTTP METHOD VALIDATOR ]
               - Verifies POST method for all state-changing requests
               - Validates CsrfHelper::validateToken($_POST['csrf_token'])
               - Halts on token mismatch (HTTP 419 / 403)
                             │
                             ▼
               [ 7. CONTROLLER / ACTION COORDINATOR ]
               - Unpacks sanitized request parameters ($_POST / $_GET)
               - Dispatches input to domain Validators
                             │ Validated Payload
                             ▼
               [ 8. DOMAIN SERVICE & CALCULATION ENGINE ]
               - Enforces business rules (e.g., monotonic odometer advance)
               - Calculates dual-criteria maintenance status
               - Executes transactional database operations (BEGIN / COMMIT)
                             │
                             ▼
               [ 9. DATA PERSISTENCE & AUDIT LOGGING ]
               - Executes PDO prepared statement with bound parameters
               - Writes security/admin audit records to audit_logs
               - Enqueues WebSocket broadcast events
                             │ Result Set / Success
                             ▼
               [ 10. VIEW RENDERING / RESPONSE DISPATCH ]
               - Renders HTML view with context-aware output escaping
               - OR returns structured JSON payload (for AJAX / API)
               - Flushes response buffer to browser
                             │
                             ▼
               [ 11. BROWSER DOM UPDATE / CLIENT NAVIGATION ]
```

---

## 2. Stage-by-Stage Responsibilities & Invariants

### Stage 1 & 2: Transport & Web Server Routing
- **Location:** `.htaccess` and Apache Web Server.
- **Invariants:**
  - Enforces HTTPS when deployed in production.
  - Injects defense headers: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`.
  - Blocks direct web access to sensitive directories (`config/`, `database/`, `logs/`, `.git`).

### Stage 3: Entry Point & Application Bootstrap
- **Location:** Target PHP script (e.g., `index.php`, `auth/login.php`, `vehicles/index.php`).
- **Invariants:**
  - Boots runtime configuration, establishes error reporting levels (`E_ALL` logged, `display_errors = 0` in production).
  - Starts secure session with `HttpOnly`, `SameSite=Lax`, and `Secure` attributes.
  - *Entry Point Decision:* The existing root `index.php` serves as the primary portal router and will **not** be relocated to a separate `public/` directory during Phase 3, preserving existing server configurations.

### Stage 4: Authentication Middleware Guard
- **Location:** `helpers/AuthHelper.php::requireAuth()`.
- **Invariants:**
  - Evaluates session validity; if expired, checks for unexpired split-token in `remember_tokens` table.
  - Regenerates session ID upon successful authentication transition (`session_regenerate_id(true)`).

### Stage 5: Role-Based Authorization Guard
- **Location:** `helpers/AuthHelper.php::requireRole('admin' | 'user')`.
- **Invariants:**
  - Verifies that the authenticated user possesses the specific required role.
  - Owner-scoped data isolation enforced on resource-level queries (`WHERE user_id = :session_user_id`).
  - System administrators denied access to private vehicle owner repair history and invoices.

### Stage 6: CSRF Token & Method Guard
- **Location:** `helpers/CsrfHelper.php::validateToken()`.
- **Invariants:**
  - Mandatory on 100% of state-changing `POST` requests.
  - Generates cryptographically secure 32-byte tokens via `random_bytes(32)`.

### Stage 7 & 8: Controller Coordination & Domain Services
- **Location:** `actions/*.php`, `admin/actions/*.php`, `helpers/MaintenanceCalculator.php`.
- **Invariants:**
  - Controllers orchestrate flow and delegate business logic to dedicated service classes.
  - Atomic transactions ensure that adding a service record with an increased odometer reading updates the vehicle record and writes a mileage log entry within the same database transaction (`PDO::beginTransaction()`).

### Stage 9: Data Persistence Layer
- **Location:** `helpers/DatabaseHelper.php` (PDO Singleton).
- **Invariants:**
  - 100% prepared SQL statements with parameter binding.
  - Zero dynamic SQL string concatenation for user-supplied values.

### Stage 10: View Rendering & Output Sanitization
- **Location:** `includes/header.php`, view files, `helpers/ViewHelper.php`.
- **Invariants:**
  - Every string rendered in HTML contexts passed through `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
  - JSON responses use `json_encode()` with `JSON_UNESCAPED_SLASHES | JSON_HEX_TAG`.

