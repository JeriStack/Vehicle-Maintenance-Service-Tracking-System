# Security Threat Model (STRIDE Evaluation)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-SEC-TM-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline, ADR-002, ADR-003, ADR-007  
**Status:** Approved Technical Design Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Executive Summary

This document presents a comprehensive, lightweight STRIDE-based security threat model for the Vehicle Maintenance Service Tracking System (VMS). Designed for an existing native PHP 8+, MariaDB, Apache, and WebSocket application, this threat model assesses potential vulnerabilities across all architectural trust boundaries and defines concrete mitigations, verification test scenarios, and residual risk assessments.

---

## 2. Asset Inventory

The system protects fourteen (14) primary information and infrastructure assets:

| Asset ID | Asset Name | Description & Sensitivity | Impact of Compromise |
|---|---|---|---|
| **AST-01** | **User Credentials** | Bcrypt password hashes, salt, and remember-me split-token hashes. | Account takeover, credential stuffing across accounts. |
| **AST-02** | **Session Identifiers** | PHP native `PHPSESSID` cookie and active session state in server storage. | Session hijacking, unauthorized identity assumption. |
| **AST-03** | **User Profile Information** | Full names, email addresses, phone numbers, addresses, account statuses. | PII exposure, targeted phishing, identity correlation. |
| **AST-04** | **Vehicle Records** | VINs, license plates, make, model, year, vehicle purchase date, current mileage. | Privacy violation, vehicle tracking, property reconnaissance. |
| **AST-05** | **Service Histories** | Dates, service types, costs, service centers, mechanic remarks, parts replaced. | Commercial privacy breach, dispute fraud, financial leakage. |
| **AST-06** | **Maintenance Schedules** | Interval rules, target dates, target mileage, recurring maintenance plans. | Service disruption, missed critical safety maintenance. |
| **AST-07** | **Mileage Records** | Historical odometer readings, timestamped progression logs. | Odometer fraud, warranty misrepresentation, vehicle valuation distortion. |
| **AST-08** | **Notification Contents** | Urgent maintenance alerts, inspection reminders, system broadcasts. | Social engineering, user confusion, spam delivery. |
| **AST-09** | **Administrator Privileges**| Administrative capability to deactivate accounts, alter roles, edit catalog. | Full platform compromise, mass unauthorized data modification. |
| **AST-10** | **Database Credentials** | Database host, username, password, database name in `config/database.php`. | Direct database takeover, data exfiltration, total integrity loss. |
| **AST-11** | **WebSocket Auth Context**| Ratchet WebSocket connection state, user ID mapping, internal broadcast socket. | Event spoofing, notification snooping, unauthorized event injection. |
| **AST-12** | **Application Secrets** | CSRF seed secrets, password reset HMAC tokens, cookie encryption keys. | Cryptographic bypass, CSRF token forgery, password reset hijack. |
| **AST-13** | **Uploaded Invoices/Docs** | Receipts, PDF inspection sheets, vehicle image attachments (`uploads/`). | Malicious script execution (Web shell), unauthenticated file access. |
| **AST-14** | **Audit Trail Logs** | Immutable administrative and system security events (`audit_logs`). | Forensic concealment, undetected tampering, non-repudiation failure. |

---

## 3. Trust Boundaries & Architecture Interconnections

The system operates across eight (8) clearly defined trust boundaries:

```text
[ GUEST / INTERNET ]
        │
    (TB-1: TLS / HTTP Request)
        ▼
[ APACHE WEB SERVER / PHP RUNTIME ] ◄──(TB-2: Session State)──► [ GUEST vs AUTH SESSIONS ]
        │                                                              │
        │                                                      (TB-3: RBAC Boundary)
        │                                                              ▼
        │                                                    [ OWNER vs ADMIN CONTEXT ]
        │
        ├───(TB-4: PDO Prepared SQL)───► [ MARIADB DATABASE ]
        │
        ├───(TB-5: TCP Socket / Loopback)───► [ RATCHET WEBSOCKET SERVER (Port 8080) ]
        │
        ├───(TB-6: File Storage IO)─────────► [ UPLOADS DIRECTORY (NoExec) ]
        │
        ├───(TB-7: CLI Environment Guard)───► [ CRON REMINDER DAEMON (CLI SAPI) ]
        │
        └───(TB-8: Composer Class Loading)──► [ THIRD-PARTY VENDOR PACKAGES ]
```

| Boundary ID | Boundary Name | Interface Description | Primary Threat Profile |
|---|---|---|---|
| **TB-1** | **Browser to Web Server** | Untrusted browser sending HTTP/HTTPS requests to Apache. | XSS, CSRF, Parameter tampering, Brute-force attacks, Injection. |
| **TB-2** | **Guest to Authenticated Session** | State transition during login and account creation. | Session fixation, Credential stuffing, Remember-me token prediction. |
| **TB-3** | **Vehicle Owner to Administrator** | Privilege boundary separating `user` and `admin` roles. | Vertical privilege escalation, Admin privacy boundary breach. |
| **TB-4** | **Application to Database** | Internal PDO queries executed against MariaDB. | SQL injection, Excessive database user privileges, Data leakage. |
| **TB-5** | **Application to WebSocket Service**| Real-time communication and push notifications over port 8080. | Socket hijacking, Cross-tenant broadcast leakage, Unauthenticated push. |
| **TB-6** | **Application to Upload Storage** | File uploads storing attachments in `uploads/` directory. | Remote code execution (Web shell upload), Path traversal, MIME confusion. |
| **TB-7** | **Application to CLI Scheduler** | Scheduled background maintenance execution via PHP CLI. | Web-accessible trigger abuse, Race conditions, Overlapping jobs. |
| **TB-8** | **Application to External Dependencies**| Composer packages (`cboden/ratchet`, `phpmailer`, `dompdf`). | Dependency vulnerabilities, Supply chain attacks, RCE in parser. |

---

## 4. Threat Evaluation & STRIDE Analysis

### 4.1 Insecure Direct Object References (IDOR) — Spoofing / Information Disclosure
- **Threat:** An authenticated Vehicle Owner modifies an ID parameter in the URL or form (e.g., `vehicle_id=5` to `vehicle_id=12`, or `service_id=45`) to access, update, or void another user's vehicle or service record.
- **Attack Surface:** `GET /vehicles/view.php?id=X`, `POST /actions/service_action.php`, `POST /actions/vehicle_action.php`, `GET /reports/generate_pdf.php?vehicle_id=X`.
- **Preconditions:** Authenticated low-privilege account.
- **Mitigation:** Strict owner scoping. Every query must enforce `WHERE id = :id AND user_id = :session_user_id` or join through `vehicles` table verifying `vehicles.user_id = :session_user_id`. Queries returning 0 rows yield `404 Not Found` or `403 Forbidden`.

### 4.2 Privilege Escalation — Elevation of Privilege
- **Threat 1 (Vertical Escalation):** Low-privilege user passes `role=admin` in the registration payload or user update form.
- **Threat 2 (Admin Privacy Breach):** System Administrator accesses private maintenance invoices, personal mechanic notes, or private reports of vehicle owners.
- **Threat 3 (Last-Admin Demotion):** Sole administrator demotes or deactivates themselves, locking the application out of administrative management.
- **Mitigation:**
  - Hardcoded role assignment in registration (`$role = 'user'`).
  - Whitelist role updates strictly through `/admin/users.php` guarded by `AuthHelper::requireRole('admin')`.
  - Admin privacy boundary: Admin controllers and navigation menus completely exclude vehicle, service, and invoice query routes.
  - Last-active-admin check prevents self-demotion or deactivation if active admin count is 1.

### 4.3 Cross-Site Request Forgery (CSRF) — Tampering
- **Threat:** Attacker tricks an authenticated user into clicking an external link or loading an `<img>` tag that triggers state-changing actions (e.g., deleting a vehicle, altering email, deactivating an account).
- **Attack Surface:** All `POST` action scripts in `/actions/` and `/admin/actions/`.
- **Mitigation:**
  - Cryptographically secure pseudo-random CSRF tokens stored in `$_SESSION['csrf_token']`.
  - Every state-changing form includes `<input type="hidden" name="csrf_token" value="...">`.
  - Handlers validate `CsrfHelper::validateToken($_POST['csrf_token'])` prior to processing.
  - Cookies set with `SameSite=Lax` and `HttpOnly=true`.

### 4.4 Cross-Site Scripting (XSS) — Tampering / Information Disclosure
- **Threat:** Stored XSS via malicious vehicle nickname, license plate, mechanic name, service remarks, or notification text executing in another user's or administrator's browser.
- **Attack Surface:** Vehicle listings, service history views, admin user tables, audit log views, in-app notification toasts.
- **Mitigation:**
  - Global context-aware output encoding using `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
  - Content Security Policy (CSP) header prohibiting inline script execution without nonce and restricting script origins.
  - Strict input sanitization discarding control characters.

### 4.5 SQL Injection (SQLi) — Information Disclosure / Tampering
- **Threat:** Attacker injects SQL payloads into filter parameters, search boxes, sorting columns, or pagination offsets.
- **Attack Surface:** Vehicle search filters, service history date-range queries, admin user search, audit log filters.
- **Mitigation:**
  - 100% parameterization using PDO prepared statements with bound parameters (`:param`).
  - Dynamic `ORDER BY` and `SORT` columns validated against strict static allowlists.
  - Numeric IDs cast strictly via `(int)$_GET['id']` before binding.

### 4.6 Session Fixation & Hijacking — Spoofing
- **Threat:** Attacker establishes a known session ID and induces victim to authenticate using it, or intercepts an unencrypted session cookie.
- **Attack Surface:** `PHPSESSID` cookie, `/auth/login.php`, `remember_me` cookie.
- **Mitigation:**
  - `session_regenerate_id(true)` executed immediately upon successful credential verification.
  - Session cookie flags: `HttpOnly = true`, `SameSite = Lax`, `Secure = true` (over HTTPS).
  - Split-token remember-me pattern (`selector` + `validator_hash`). Constant-time comparison `hash_equals()`.
  - Active session invalidation and database token revocation upon explicit logout.

### 4.7 Account Abuse & Brute-Force — Denial of Service / Spoofing
- **Threat:** Automated credential stuffing, password guessing against admin accounts, registration flooding.
- **Attack Surface:** `POST /actions/login_action.php`, `POST /actions/register_action.php`, `POST /actions/forgot_password_action.php`.
- **Mitigation:**
  - IP and account-based rate limiting (5 failed attempts per 15-minute window).
  - Exponential backoff / login delay.
  - Generic authentication error messages (`"Invalid email or password"`) preventing account enumeration.

### 4.8 Application Secret & Configuration Exposure — Information Disclosure
- **Threat:** Direct web access to configuration files, `.env` files, `.git` repository, database backups, or verbose error traces revealing credentials and schema details.
- **Attack Surface:** `GET /config/database.php`, `GET /.git/HEAD`, `GET /database/schema.sql`, unhandled PHP exceptions.
- **Mitigation:**
  - Apache `.htaccess` deny rules blocking access to `.git`, `.env`, `*.sql`, `*.log`, and `config/`.
  - Production error reporting configured to log silently (`display_errors = 0`, `log_errors = 1`).
  - Sensitive environment variables stored outside document root or sanitized in version control.

### 4.9 Audit Log Tampering & Repudiation — Repudiation
- **Threat:** Rogue administrator or compromised script alters or truncates `audit_logs` to conceal unauthorized operations.
- **Attack Surface:** Database direct access, administrative audit log interface.
- **Mitigation:**
  - Application layer provides **ZERO** routes for updating or deleting audit logs (read-only interface).
  - Database user privileges for standard web application restricted where possible.
  - High-privilege events log actor ID, target user ID, IP address, user agent, action, and timestamp.

### 4.10 WebSocket Spoofing & Eavesdropping — Information Disclosure / Tampering
- **Threat:** Unauthorized client connects to port 8080 and listens to private real-time notification broadcasts or injects fake maintenance alerts.
- **Attack Surface:** Ratchet WebSocket server on `ws://localhost:8080`.
- **Mitigation:**
  - WebSocket connection handshake validates session cookie or authenticated ephemeral ticket.
  - Client subscription topic strictly scoped to `user_{session_user_id}`.
  - Inbound broadcast messages accepted strictly from local server loopback (`127.0.0.1`) or processed internally from `realtime_events` table.

### 4.11 File Upload Remote Code Execution (RCE) — Elevation of Privilege / Tampering
- **Threat:** Attacker uploads `shell.php` masked as a receipt image or uses path traversal (`../../shell.php`) to execute arbitrary code.
- **Attack Surface:** Vehicle document upload, service invoice attachment.
- **Mitigation:**
  - Strict extension allowlist: `.jpg`, `.jpeg`, `.png`, `.pdf`.
  - Server-side MIME verification using PHP `finfo` (not trusting client-supplied `Content-Type`).
  - Stored files renamed to cryptographically random UUIDs (`bin2hex(random_bytes(16)) . '.' . $ext`).
  - `.htaccess` in `uploads/` directory with `php_flag engine off` and `Options -ExecCGI`.

---

## 5. Comprehensive Threat Register

| Threat ID | Asset | Attack Surface | Threat Description | Preconditions | Impact | Likelihood | Severity | Mitigation Control | Verification Test | Residual Risk | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| **THR-01** | AST-04, AST-05 | Vehicle / Service URLs | IDOR: Tampering with `id` parameter to read/modify foreign vehicle or service record | Authenticated User | High | High | **CRITICAL** | Enforce `WHERE user_id = :uid` and verify vehicle parent ownership | Automated IDOR penetration test (`tests/Unit/AuthorizationTest.php`) | Low | **Mitigated (Design)** |
| **THR-02** | AST-09 | Registration / User POST | Privilege Escalation: Injecting `role=admin` or altering own role | Guest or User | Critical | Medium | **HIGH** | Whitelist role setting; hardcode user registration role to `user` | Role injection assertion test (`tests/Unit/AuthTest.php`) | Low | **Mitigated (Design)** |
| **THR-03** | AST-09, AST-03 | Admin Management | Sole Admin Lockout: Deactivating or demoting the last active administrator | Admin Session | High | Low | **MEDIUM** | Enforce minimum active admin count guard before status/role change | Last-admin protection test (`tests/Unit/AdminGovernanceTest.php`) | Low | **Mitigated (Design)** |
| **THR-04** | AST-04, AST-05 | Admin Views | Admin Privacy Breach: Administrator inspecting private owner invoices/repairs | Admin Session | Medium | Medium | **MEDIUM** | Strict routing boundary; admin role denied access to owner repair routes | Admin access barrier test (`tests/Unit/AdminPrivacyTest.php`) | Low | **Mitigated (Design)** |
| **THR-05** | AST-01, AST-04 | All Action Handlers | CSRF: Forcing victim to perform unauthorized state changes | Authenticated Victim | High | Medium | **HIGH** | Mandatory CSRF token check on all POST requests; `SameSite=Lax` | CSRF token missing/invalid test (`tests/Unit/CsrfValidationTest.php`) | Low | **Mitigated (Design)** |
| **THR-06** | AST-02, AST-03 | Web Views | Stored XSS: Injecting script in vehicle notes or service remarks | Authenticated User | High | Medium | **HIGH** | `htmlspecialchars(..., ENT_QUOTES)` on all output; CSP headers | XSS string rendering test with HTML entity assertions | Low | **Mitigated (Design)** |
| **THR-07** | AST-10, AST-04 | SQL Queries | SQL Injection: Injecting SQL syntax through search/filter inputs | Any Web User | Critical | Low | **HIGH** | 100% PDO prepared statements; strict column allowlists | SQL injection attack strings in search tests | Low | **Mitigated (Design)** |
| **THR-08** | AST-01, AST-02 | Auth Endpoints | Credential Stuffing / Brute-force: Repeated password guessing | Public Internet | High | High | **HIGH** | Login rate limiting (5 attempts/15 mins); account lockout; backoff | Throttling verification test (`tests/Unit/RateLimitTest.php`) | Medium | **Mitigated (Design)** |
| **THR-09** | AST-02 | Login / Logout | Session Fixation / Stale Session: Reusing unregenerated session | Authenticated User | High | Medium | **MEDIUM** | `session_regenerate_id(true)` upon auth; destroy session on logout | Session ID mutation check before and after authentication | Low | **Mitigated (Design)** |
| **THR-10** | AST-13 | File Uploads | Unrestricted File Upload / RCE: Uploading executable PHP script | Authenticated User | Critical | Medium | **CRITICAL** | Extension whitelist; MIME check; UUID rename; `.htaccess` NoExec | Upload test attempting `.php`, `.phtml`, `.exe` files | Low | **Mitigated (Design)** |
| **THR-11** | AST-14 | Audit Log Table | Audit Log Tampering: Deleting or modifying audit records | Admin Session | High | Low | **MEDIUM** | Read-only UI; application layer has no UPDATE/DELETE queries on table | Code audit verifying absence of mutation routes | Low | **Mitigated (Design)** |
| **THR-12** | AST-11 | WebSocket Port | WebSocket Snooping / Injection: Listening to other users' topics | Any Web User | Medium | Medium | **MEDIUM** | Token-authenticated handshake; channel isolation by user ID | Cross-channel subscription unauthorized rejection test | Low | **Mitigated (Design)** |
| **THR-13** | AST-07 | Service Records | Odometer Rollback / Fraud: Entering lower mileage than current | Authenticated User | Medium | Medium | **MEDIUM** | Monotonic validation (`service_mileage >= current_mileage`) | Negative test submitting lower odometer value | Low | **Mitigated (Design)** |
| **THR-14** | AST-08 | CLI Daemon | Reminder Flooding: Duplicate reminder notifications generated | System Scheduler | Low | Medium | **LOW** | Deduplication key (`sha256(veh+sched+type+date)`) + file lock | Concurrency test with parallel scheduler invocations | Low | **Mitigated (Design)** |

---

## 6. Security Testing & Verification Plan

All fourteen threats documented above map to automated unit or integration tests:

1. **Authorization & IDOR Suite (`tests/Unit/AuthorizationTest.php`):**
   - Asserts that User A cannot read, update, or archive User B's vehicle.
   - Asserts that User A cannot view User B's service records or download their invoice.
   - Asserts that User A cannot mark User B's notification as read.
2. **Authentication & Session Suite (`tests/Unit/AuthTest.php`):**
   - Asserts session regeneration upon successful login.
   - Asserts split-token verification with constant-time comparison.
   - Asserts that registration ignores any supplied `role` parameter.
3. **Governance & Admin Suite (`tests/Unit/AdminGovernanceTest.php`):**
   - Asserts rejection when attempting to demote or deactivate the last active administrator.
   - Asserts that admin endpoints deny access to users with `role = 'user'`.
4. **Input Defense Suite (`tests/Unit/InputDefenseTest.php`):**
   - Asserts XSS payloads are properly escaped when rendered.
   - Asserts SQL injection payloads are safely handled by PDO without syntax disruption.
5. **Scheduler & Concurrency Suite (`tests/Unit/SchedulerTest.php`):**
   - Asserts that duplicate notifications are ignored via deduplication hashing.
   - Asserts that non-CLI execution aborts with `HTTP 403 Forbidden`.

---

## 7. Residual Risks & Accepted Decisions

1. **Self-Hosted WebSocket (Port 8080):** In environments where port 8080 is blocked by external firewalls, clients fall back to database polling (`realtime_events`). This is an accepted operational fallback.
2. **Database Superuser Credentials:** The local development environment uses MariaDB `root` without a password. In production, this must be replaced by a dedicated `vms_app` user with restricted `SELECT, INSERT, UPDATE` privileges (no `DROP` or `ALTER`). This is documented in deployment configuration requirements.

