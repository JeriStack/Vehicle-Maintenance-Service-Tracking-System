# Environment & Version Baseline
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ENV-001  
**Phase:** 0 — Project Initiation and Feasibility  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Primary Development Environment Selection

During Phase 0, two primary operating environments were evaluated against the existing codebase:

* **Option A: XAMPP / Apache on Windows (SELECTED PRIMARY ENVIRONMENT)**
  * *Rationale:* The existing repository was built, tested, and structurally organized for native execution within the XAMPP web root (`C:\xampp\htdocs\GitHub_Clone_File_Projects\Vehicle-Maintenance-Service-Tracking-System`). Apache `.htaccess` directives, relative directory structures, and Windows batch scripts (`start_websocket.bat`) are fully established and validated.
* **Option B: Containerized Docker Environment (REJECTED FOR PHASE 0)**
  * *Rationale:* No `Dockerfile` or `docker-compose.yml` was originally present in the repository. Introducing Docker at Phase 0 would require containerizing the PHP runtime, MariaDB, and the background WebSocket daemon without architectural necessity, introducing virtualization overhead for the local team.

> [!IMPORTANT]
> **Decision D-001 Confirmed:** Option A (XAMPP / Apache on Windows) is formally established as the **single primary development and testing environment**.

---

## 2. Technical Stack Version Baseline

The following versions have been directly verified through live CLI diagnostics on the host system:

| Component | Verified Tested Version | Target Production Compatibility | Verification Command / Evidence | Status |
|---|---|---|---|---|
| **PHP Runtime** | `8.5.12` (ZTS Visual C++ 2022 x64) | `8.0.0` - `8.5.x` | `php -v` (Zend Engine v4.5.12) | Verified |
| **Database Engine** | `MariaDB 10.4.32` (Win64 Distrib) | MySQL 8.0+ / MariaDB 10.4+ | `C:\xampp\mysql\bin\mysql.exe --version` | Verified |
| **Web Server** | `Apache/2.4.58 (Win64)` | Apache 2.4.x with `mod_rewrite` | `C:\xampp\apache\bin\httpd.exe -v` | Verified |
| **Dependency Manager**| `Composer 2.10.2` | Composer 2.x | `composer --version` | Verified |
| **Test Runner** | Native Runner & `PHPUnit 9.6+` | PHPUnit 9 / 10 compatible | `composer test` (44 tests pass) | Verified |
| **WebSocket Daemon** | Native PHP RFC 6455 Socket Stream | PHP CLI streaming socket (`127.0.0.1:8080`) | `php websocket/server.php` | Verified |
| **Web Browsers** | Chrome 120+, Edge 120+, Firefox 120+ | Modern Evergreen Browsers | Tested on Windows 11 host | Verified |

---

## 3. Required PHP Extensions

The following extensions are verified as active and loaded in the runtime environment (`php -m`):

* `pdo` & `pdo_mysql`: Mandatory for parameterized MySQL database transactions.
* `session`: Mandatory for cookie-based authentication, flash messages, and CSRF token storage.
* `json`: Mandatory for API endpoints, WebSocket JSON framing, and Chart.js payloads.
* `mbstring`: Mandatory for UTF-8 multibyte string sanitization and length validation.
* `openssl`: Mandatory for cryptographically secure pseudo-random bytes (`random_bytes()`) and SMTP STARTTLS.
* `filter`: Mandatory for secure input sanitization (`filter_input()`).
* `hash`: Mandatory for password resets and remember-me token hashing (`hash('sha256', ...)`).
* `date`: Mandatory for timestamp calculations and timezone transformations.
* `bcmath`: Mandatory for precision decimal cost calculations.
* `fileinfo`: Required for MIME type validation on document/invoice uploads.
* `curl`: Available for external HTTP communication.

---

## 4. Regional & Localization Standards

To prevent formatting ambiguities across code, database schemas, and user interfaces, the following regional baseline is established:

```text
┌─────────────────────────────────────────────────────────────┐
│                 VMS REGIONAL BASELINE                       │
├─────────────────────┬───────────────────────────────────────┤
│ Currency            │ Philippine Peso (PHP, ₱)              │
│ Formatting Pattern  │ ₱1,234.50 (via formatCurrency())      │
├─────────────────────┼───────────────────────────────────────┤
│ Timezone            │ Asia/Manila (UTC+8)                   │
│ Configuration Key   │ date_default_timezone_set('Asia/Manila│
├─────────────────────┼───────────────────────────────────────┤
│ Distance Unit       │ Kilometres (KM)                       │
│ Odometer Rule       │ Monotonic non-negative integer (KM)   │
└─────────────────────┴───────────────────────────────────────┘
```

> [!NOTE]
> All datetime stamps in the database are stored in `TIMESTAMP` / `DATETIME` formats. `config/app.php` should align to `Asia/Manila` to ensure reminders match local vehicle owners' calendar days.

---

## 5. Web Server Configuration & Security Rules

Apache executes the application through `c:\xampp\htdocs\GitHub_Clone_File_Projects\Vehicle-Maintenance-Service-Tracking-System\.htaccess`:

1. **Directory Browsing Disabled:** `Options -Indexes` prevents public directory traversal.
2. **Sensitive File Cloaking:** Direct HTTP requests targeting `.env`, `.ini`, `.log`, `.sql`, `.json`, `.md`, and `.git` are denied via `<FilesMatch>` directives.
3. **HTTP Security Headers:**
   * `X-Content-Type-Options "nosniff"`
   * `X-Frame-Options "SAMEORIGIN"` (clickjacking mitigation)
   * `X-XSS-Protection "1; mode=block"`
   * `Referrer-Policy "strict-origin-when-cross-origin"`
4. **Error Handling:** Custom HTTP 403 and 404 handlers route through the central application landing controller.

---

## 6. Real-Time WebSocket Infrastructure

* **Protocol:** RFC 6455 WebSocket protocol.
* **Server Implementation:** Pure native PHP socket stream (`stream_socket_server('tcp://127.0.0.1:8080')`) located at `websocket/server.php`.
* **Zero External Dependencies:** Functions entirely without Ratchet, Node.js, or external brokers.
* **Dual-Mode Delivery:**
  1. *Primary Transport:* Non-blocking direct TCP loopback socket broadcast (`helpers/websocket_helper.php`).
  2. *Fallback Persistence:* Database queue (`realtime_events` table) consumed by the daemon every loop iteration, guaranteeing message delivery even when socket transport is momentarily restricted.
* **Service Launchers:** `start_websocket.bat` (Windows) and `start_websocket.sh` (Linux).

---

## 7. Email & SMTP Configuration

* **Configuration File:** `config/mail.php`
* **Mailer Client:** Native RFC 5321 socket client (`helpers/mail_helper.php`) supporting `LOGIN` authentication and `STARTTLS` encryption.
* **Modes:**
  * `MAIL_DRIVER = 'smtp'`: Live production delivery (Gmail App Passwords, SendGrid, Mailgun).
  * `MAIL_DRIVER = 'log'`: Offline development mode writing full email headers and reset links to `logs/mail.log` without requiring internet connectivity.

---

## 8. Outstanding Environment Decisions

* **Timezone Configuration:** Currently, `config/app.php` specifies `date_default_timezone_set('UTC')`. Proposal D-006 formally recommends updating this default to `'Asia/Manila'` to align with the regional standard.

