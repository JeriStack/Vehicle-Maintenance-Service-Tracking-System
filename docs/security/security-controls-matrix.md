# Security Controls Matrix
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-SEC-SCM-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline, STRIDE Threat Model (VMS-DOC-SEC-TM-001)  
**Status:** Approved Technical Design Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Purpose & Scope

This document specifies the technical security controls implemented across the Vehicle Maintenance Service Tracking System (VMS). It establishes direct traceability between the approved security requirements (Phase 1), identified STRIDE threats (VMS-DOC-SEC-TM-001), application architecture decisions (ADR-001 through ADR-008), and their concrete software implementations.

---

## 2. Technical Security Controls Matrix

| Control Category | Control ID | Security Objective | Threat Addressed | Technical Implementation Standard | Code / Component Location | Enforcement Status |
|---|---|---|---|---|---|---|
| **Cryptographic Standards** | **CTL-CRY-01** | Secure Password Hashing | Credential Exfiltration / Rainbow Tables | Bcrypt algorithm via `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` | `helpers/AuthHelper.php`, `actions/register_action.php` | **IMPLEMENTED** |
| | **CTL-CRY-02** | Split-Token Authentication | Remember-Me Cookie Prediction / Timing Attacks | 16-byte selector (plaintext) + 32-byte validator hashed with SHA-256 (`hash('sha256', $validator)`). Constant-time verification with `hash_equals()`. | `helpers/AuthHelper.php`, `auth/login.php` | **IMPLEMENTED** |
| | **CTL-CRY-03** | Secure Random Number Generation | Token Prediction / Session Hijack | `random_bytes(32)` used for CSRF tokens, remember-me validators, and password reset tokens | `helpers/CsrfHelper.php`, `helpers/AuthHelper.php` | **IMPLEMENTED** |
| | **CTL-CRY-04** | Notification Deduplication Hashing | Reminder Flooding / Race Conditions | Deterministic SHA-256 hash of `vehicle_id + schedule_id + trigger_type + due_date` | `reminders/cron_reminders.php` | **DESIGNED** |
| **Authentication & Session** | **CTL-AUT-01** | Session Regeneration | Session Fixation | `session_regenerate_id(true)` executed immediately after successful login verification | `actions/login_action.php`, `helpers/AuthHelper.php` | **IMPLEMENTED** |
| | **CTL-AUT-02** | Hardened Cookie Flags | XSS Cookie Theft / Cross-Site Leaks | Session & remember cookies configured with `HttpOnly = true`, `SameSite = Lax`, `Secure = true` (when HTTPS active), `Path = /` | `config/session.php`, `helpers/AuthHelper.php` | **IMPLEMENTED** |
| | **CTL-AUT-03** | Explicit Session Invalidation | Stale Session Reuse / Ghost Sessions | Logout executes `$_SESSION = []`, deletes cookie from browser, and calls `session_destroy()` + database token revocation | `auth/logout.php`, `helpers/AuthHelper.php` | **IMPLEMENTED** |
| | **CTL-AUT-04** | Generic Auth Error Messaging | Account Enumeration / User Reconnaissance | Uniform error message: `"Invalid email address or password."` regardless of whether email exists | `actions/login_action.php` | **IMPLEMENTED** |
| | **CTL-AUT-05** | Login Rate Limiting | Brute-force / Credential Stuffing | Maximum 5 failed attempts per 15-minute window tracked by IP and email. Exponential backoff delay. | `helpers/RateLimiter.php` | **DESIGNED** |
| **Access Control (RBAC)** | **CTL-ACC-01** | Server-Side Role Enforcement | Vertical Privilege Escalation | Explicit role check via `AuthHelper::requireRole('admin')` or `AuthHelper::requireRole('user')` on every protected route | All view and action scripts | **IMPLEMENTED** |
| | **CTL-ACC-02** | Strict Owner Scoping (IDOR Immunity) | Horizontal Privilege Escalation / Data Snooping | Direct database scoping: `WHERE user_id = :session_user_id` or `INNER JOIN vehicles v ON ... WHERE v.user_id = :session_user_id` | All vehicle and service controllers | **IMPLEMENTED** |
| | **CTL-ACC-03** | Admin Privacy Boundary | Administrator Overreach / Private Data Exposure | System administrator role explicitly prohibited from accessing owner vehicle repair logs, invoices, and personal inboxes | `admin/*`, `reports/*`, `vehicles/*` | **IMPLEMENTED** |
| | **CTL-ACC-04** | Last Administrator Protection | Platform Administrative Lockout | Validation check preventing demotion or deactivation of the sole remaining active administrator | `admin/actions/update_user.php` | **DESIGNED** |
| | **CTL-ACC-05** | Immutable Role Registration | Self-Promotion at Signup | User registration script hardcodes `role = 'user'`, completely ignoring any client-submitted `role` field | `actions/register_action.php` | **IMPLEMENTED** |
| **Data Integrity & DB** | **CTL-DAT-01** | Mandatory Parameterization | SQL Injection | 100% of SQL statements executed via PDO prepared statements with bound parameters (`bindValue()` / `execute()`) | `helpers/DatabaseHelper.php`, all models | **IMPLEMENTED** |
| | **CTL-DAT-02** | Sort & Column Allowlisting | SQL Injection via Dynamic Order Clauses | Table column and direction parameters validated against strict compile-time white-lists before SQL interpolation | `helpers/QueryHelper.php` | **IMPLEMENTED** |
| | **CTL-DAT-03** | Monotonic Mileage Enforcement | Odometer Rollback / Warranty Fraud | New service or mileage log odometer readings validated `service_mileage >= current_mileage` within database transaction | `actions/service_action.php`, `models/Vehicle.php` | **DESIGNED** |
| | **CTL-DAT-04** | Soft Deletion & Audit Invariants | Accidental / Malicious Data Deletion | Primary business entities (`vehicles`) use `status = 'archived'`. Service records voided (`status = 'Voided'`) with reason. | `models/Vehicle.php`, `models/ServiceRecord.php` | **IMPLEMENTED** |
| **Client-Side Defense** | **CTL-CLI-01** | Context-Aware Output Escaping | Cross-Site Scripting (XSS) | All untrusted user, vehicle, or log strings passed through `htmlspecialchars($str, ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')` | All presentation templates | **IMPLEMENTED** |
| | **CTL-CLI-02** | Synchronized CSRF Tokens | Cross-Site Request Forgery | Per-session cryptographic CSRF token validated on all state-altering POST requests | `helpers/CsrfHelper.php` | **IMPLEMENTED** |
| | **CTL-CLI-03** | Content Security Policy Headers | Inline Script Injection / Exfiltration | HTTP header `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-...'; style-src 'self' 'unsafe-inline'` | `includes/header.php`, `.htaccess` | **DESIGNED** |
| | **CTL-CLI-04** | MIME Sniffing & Framing Defense | Clickjacking / Content Spoofing | HTTP headers `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` | `.htaccess`, `includes/header.php` | **IMPLEMENTED** |
| **File Storage Security** | **CTL-FIL-01** | Strict File Extension Allowlist | Remote Code Execution via Upload | Allowed extensions strictly limited to `['jpg', 'jpeg', 'png', 'pdf']`. Executable extensions rejected. | `helpers/UploadHelper.php` | **DESIGNED** |
| | **CTL-FIL-02** | Cryptographic File Renaming | Path Traversal / Direct Execution | Uploaded files stored as random hex UUIDs (`bin2hex(random_bytes(16)) . '.' . $ext`) discarding original filename | `helpers/UploadHelper.php` | **DESIGNED** |
| | **CTL-FIL-03** | Web-Server NoExec Directory | Web-Shell Execution | Dedicated `.htaccess` inside `uploads/` specifying `php_flag engine off` and `Options -ExecCGI` | `uploads/.htaccess` | **DESIGNED** |
| **Background Processing**| **CTL-BKG-01** | CLI Execution Boundary | Unauthorized Web Trigger of Scheduled Jobs | CLI check `php_sapi_name() === 'cli'` halts with HTTP 403 if accessed via web server | `reminders/cron_reminders.php` | **IMPLEMENTED** |
| | **CTL-BKG-02** | File-Lock Concurrency Guard | Overlapping Scheduler Executions / Race Conditions | Non-blocking exclusive file lock via `flock($fp, LOCK_EX \| LOCK_NB)` on `cron_reminders.lock` | `reminders/cron_reminders.php` | **DESIGNED** |
| **Audit & Forensics** | **CTL-AUD-01** | Immutable Audit Trail | Forensic Concealment / Repudiation | Privileged administrative actions, security violations, and role alterations recorded in append-only `audit_logs` | `helpers/AuditHelper.php`, `admin/*` | **IMPLEMENTED** |
| | **CTL-AUD-02** | Audit Record Context Completeness | Spoofed Actor Attribution | Each audit log records `user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`, `user_agent`, and `created_at` | `database/schema.sql`, `helpers/AuditHelper.php` | **IMPLEMENTED** |

---

## 3. Verification & Compliance Traceability

| Security Control | Verification Procedure | PHPUnit Test Class |
|---|---|---|
| **CTL-CRY-01 / 02** | Verify password verification failure with wrong credentials; verify split-token resolution | `tests/Unit/AuthTest.php` |
| **CTL-AUT-01** | Verify session ID changes after authentication | `tests/Unit/AuthTest.php` |
| **CTL-ACC-01 / 02** | Attempt IDOR manipulation of `vehicle_id` and role tampering | `tests/Unit/AuthorizationTest.php` |
| **CTL-ACC-03** | Verify admin account receives 403 on vehicle owner service routes | `tests/Unit/AdminPrivacyTest.php` |
| **CTL-CLI-01** | Verify rendering of strings containing `<script>alert(1)</script>` results in escaped HTML entities | `tests/Unit/SecurityTest.php` |
| **CTL-CLI-02** | Submit POST request with omitted or invalid CSRF token and verify HTTP 403 rejection | `tests/Unit/CsrfValidationTest.php` |
| **CTL-BKG-01** | Verify CLI guard terminates with non-zero or 403 under simulated web SAPI | `tests/Unit/CliSecurityTest.php` |

