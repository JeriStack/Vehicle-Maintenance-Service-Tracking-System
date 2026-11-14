# Decision Register
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DR-001  
**Phase:** 0 — Project Initiation and Feasibility  
**Status:** Maintained & Active  
**Last Updated:** 2026-11-05  

---

## Decision Log Summary

This register formally tracks technical, architectural, and business decisions governing the VMS application. Each decision records the evaluated alternatives, rationale, system impact, and formal approval state.

| Decision ID | Date | Decision Title | Options Evaluated | Selected Decision | Status |
|---|---|---|---|---|---|
| **D-001** | 2026-11-05 | Primary Development Environment | XAMPP / Docker / Cloud VM | **XAMPP (Apache + MariaDB on Windows)** | Confirmed Baseline |
| **D-002** | 2026-11-05 | Backend Technology Architecture | Native PHP 8+ / Laravel / CodeIgniter | **Native PHP 8+ with PDO** | Confirmed Baseline |
| **D-003** | 2026-11-05 | Database Management System | MariaDB / MySQL / PostgreSQL | **MariaDB 10.4+ / MySQL 8.0+ (InnoDB)** | Confirmed Baseline |
| **D-004** | 2026-11-05 | Application User Role Model | Single Role / 2 Roles / 3 Roles (w/ Mechanic) | **Two-Role Model (SYSTEM_ADMIN, VEHICLE_OWNER)** | Approved Baseline |
| **D-005** | 2026-11-05 | Application Currency Standard | Philippine Peso (PHP) / USD | **Philippine Peso (PHP, ₱)** | Proposed (Awaiting Gate) |
| **D-006** | 2026-11-05 | Default System Timezone | Asia/Manila / UTC / America/New_York | **Asia/Manila (UTC+8)** | Proposed (Awaiting Gate) |
| **D-007** | 2026-11-05 | Standard Distance Measurement Unit | Kilometres (KM) / Miles (MI) | **Kilometres (KM)** | Proposed (Awaiting Gate) |
| **D-008** | 2026-11-05 | Vehicle Deletion Architecture | Hard Delete CASCADE / Soft Archive | **Soft Archive (`status = 'archived'`)** | Proposed for Phase 1 |
| **D-009** | 2026-11-05 | Real-Time Notification Transport | Native RFC 6455 / Pusher / Polling Only | **Native RFC 6455 Daemon + DB Queue** | Implemented & Verified |
| **D-010** | 2026-11-05 | Password Reset & Email Strategy | Native RFC 5321 Socket / PHPMailer / Mailgun SDK | **Native RFC 5321 Socket w/ Log Fallback** | Implemented & Verified |
| **D-011** | 2026-11-05 | Automated Unit Testing Framework | Zero-Dependency Runner / PHPUnit / Pest | **Dual Native Runner & PHPUnit Support** | Implemented & Verified |

---

## Detailed Decision Records

### D-001: Primary Development Environment
- **Date:** 2026-11-05
- **Description:** Select the official, reproducible local development and testing environment.
- **Options Evaluated:**
  1. *Option A (XAMPP on Windows):* Native execution with Apache 2.4, MariaDB 10.4, and PHP 8.x.
  2. *Option B (Docker Containers):* Multi-container setup with `docker-compose`.
- **Selected Decision:** Option A (XAMPP on Windows).
- **Rationale:** The repository is already structured for XAMPP `htdocs`, includes Windows batch launchers, and is verified working without container overhead.
- **Impact:** Setup instructions in `SETUP_NOTES.txt` focus primarily on XAMPP, minimizing onboarding friction.
- **Status:** **Confirmed Baseline**

### D-002: Backend Architecture
- **Date:** 2026-11-05
- **Description:** Determine whether to continue using native PHP or migrate to an external framework.
- **Options Evaluated:**
  1. *Option A (Native PHP 8+ with PDO):* Retain existing modular structure, custom helpers, and direct SQL queries.
  2. *Option B (Framework Migration to Laravel/CodeIgniter):* Complete rewrite.
- **Selected Decision:** Option A (Native PHP 8+).
- **Rationale:** Preserves existing verified code, guarantees zero external framework dependencies, and complies with core project instructions forbidding greenfield rewrites.
- **Impact:** Eliminates third-party deprecation risks; enforces clean modular engineering inside `/includes`, `/actions`, and `/helpers`.
- **Status:** **Confirmed Baseline**

### D-003: Database Management System
- **Date:** 2026-11-05
- **Description:** Define the target relational database platform.
- **Options Evaluated:**
  1. *Option A (MariaDB 10.4+ / MySQL 8.0+ with InnoDB):* Standard relational database available in XAMPP.
  2. *Option B (PostgreSQL):* Requires separate service installation.
- **Selected Decision:** Option A (MariaDB / MySQL).
- **Rationale:** Matches the existing `database/schema.sql` syntax and XAMPP bundled services.
- **Impact:** Full support for foreign keys, index optimization, transactions, and JSON functions.
- **Status:** **Confirmed Baseline**

### D-004: Application User Role Model
- **Date:** 2026-11-05
- **Description:** Define user role boundaries and determine whether mechanics/shops receive user accounts.
- **Options Evaluated:**
  1. *Option A (Two Roles):* `SYSTEM_ADMIN` and `VEHICLE_OWNER`. Mechanics are descriptive fields.
  2. *Option B (Three Roles):* Adding a `MECHANIC` role with authenticated shop portals.
- **Selected Decision:** Option A (Two Roles).
- **Rationale:** Fully aligns with the approved system plan and UML blueprints. Prevents scope creep, unauthorized data access, and unneeded portal complexity.
- **Impact:** Centralized RBAC helpers (`isAdmin()`, `isUser()`) enforce access across all endpoints.
- **Status:** **Approved Baseline**

### D-005: Application Currency Standard
- **Date:** 2026-11-05
- **Description:** Establish default monetary representation.
- **Options Evaluated:**
  1. *Option A (Philippine Peso - PHP, ₱):* Primary deployment context.
  2. *Option B (US Dollar - USD, $):* Generic standard.
- **Selected Decision:** Option A (Philippine Peso - PHP, ₱).
- **Rationale:** Aligns with deployment target and course project requirements.
- **Impact:** Visual formatting helper `formatCurrency()` formats values with `₱` prefix and two decimal places.
- **Status:** **Proposed (Awaiting Academic Gate Approval)**

### D-006: Default System Timezone
- **Date:** 2026-11-05
- **Description:** Set default timezone for dates, timestamps, and scheduled notifications.
- **Options Evaluated:**
  1. *Option A (Asia/Manila - UTC+8):* Local Philippine timezone.
  2. *Option B (UTC):* Universal Coordinated Time.
- **Selected Decision:** Option A (Asia/Manila).
- **Rationale:** Ensures daily reminder cron jobs and midnight status evaluations match vehicle owners' local calendar days.
- **Impact:** Updating `config/app.php` to set `date_default_timezone_set('Asia/Manila')`.
- **Status:** **Proposed (Awaiting Academic Gate Approval)**

### D-007: Standard Distance Measurement Unit
- **Date:** 2026-11-05
- **Description:** Choose unit of measurement for vehicle odometers and maintenance intervals.
- **Options Evaluated:**
  1. *Option A (Kilometres - KM):* Standard metric unit in the Philippines.
  2. *Option B (Miles - MI):* Imperial unit.
- **Selected Decision:** Option A (Kilometres - KM).
- **Rationale:** Standard vehicle metric unit in the Philippine automotive landscape.
- **Impact:** Default intervals set to metric numbers (e.g., 5,000 km, 10,000 km, 500 km reminder window).
- **Status:** **Proposed (Awaiting Academic Gate Approval)**

### D-008: Vehicle Deletion Architecture
- **Date:** 2026-11-05
- **Description:** Determine handling of vehicle deletion requests.
- **Options Evaluated:**
  1. *Option A (Destructive Hard Delete):* `DELETE FROM vehicles WHERE id = ?` with CASCADE to services.
  2. *Option B (Soft Archive):* `UPDATE vehicles SET status = 'archived' WHERE id = ?`.
- **Selected Decision:** Option B (Soft Archive).
- **Rationale:** Hard deletion destroys past expenditure history, corrupting lifetime financial summaries and historical maintenance records.
- **Impact:** In Phase 1, `actions/vehicle_action.php` will update status to `'archived'`, and active list queries will filter by `WHERE status != 'archived'`.
- **Status:** **Proposed for Phase 1 Implementation**

### D-009: Real-Time Notification Transport
- **Date:** 2026-11-05
- **Description:** Select mechanism for delivering instantaneous UI notifications.
- **Options Evaluated:**
  1. *Option A (Native RFC 6455 Daemon + Database Queue):* Pure PHP socket server with DB fallback.
  2. *Option B (Third-Party SaaS like Pusher):* Requires external API keys and internet.
  3. *Option C (Short Polling Only):* Creates heavy continuous HTTP overhead.
- **Selected Decision:** Option A (Native RFC 6455 Daemon + Database Queue).
- **Rationale:** Zero external cost, works completely offline, zero composer runtime dependencies, and delivers instant alerts.
- **Impact:** Real-time push enabled via `websocket/server.php` and `assets/js/notifications.js`.
- **Status:** **Implemented & Verified**

### D-010: Password Reset & Email Strategy
- **Date:** 2026-11-05
- **Description:** Implement email delivery for "Forgot Password" tokens.
- **Options Evaluated:**
  1. *Option A (Native RFC 5321 Socket Client with Log Fallback):* Pure PHP socket SMTP client with `logs/mail.log` fallback.
  2. *Option B (PHPMailer Composer Library):* Adds dependency.
- **Selected Decision:** Option A.
- **Rationale:** Supports live SMTP (e.g. Gmail App Password) and offline local development file logging without external packages.
- **Impact:** Works in any environment without third-party libraries.
- **Status:** **Implemented & Verified**

### D-011: Automated Unit Testing Framework
- **Date:** 2026-11-05
- **Description:** Provide automated test harness for CI and local verification.
- **Options Evaluated:**
  1. *Option A (Dual Native Test Runner & PHPUnit XML):* Zero-dependency standalone runner (`tests/run_tests.php`) compatible with standard PHPUnit conventions.
  2. *Option B (Mandatory Heavy Test Framework Only):* Fails in environments lacking Composer.
- **Selected Decision:** Option A.
- **Rationale:** Allows running tests via `php tests/run_tests.php` or `composer test` without installing external dependencies.
- **Impact:** 44 passing unit tests verifying authentication, helpers, and calculation logic.
- **Status:** **Implemented & Verified**

