# Application Architecture Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ARC-APP-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, ADR-001 through ADR-008  
**Status:** Approved Architecture Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Architectural Philosophy & Guiding Principles

The Vehicle Maintenance Service Tracking System (VMS) is architected as a **robust, high-performance, native PHP 8+ web application** adhering to separation of concerns without imposing the heavy overhead or runtime bloat of full-stack monolithic frameworks.

### Core Architectural Pillars:
1. **Zero-Dependency Native Core:** Core business rules, database transactions, session management, and routing run on standard PHP 8+ built-ins and PDO, ensuring rapid cold-start times (< 20ms) and minimal operational surface.
2. **Layered Separation of Concerns:** Strict boundaries divide presentation views, transactional action handlers, domain calculation services, and database persistence repositories.
3. **Strict Owner-Scoped Data Isolation (IDOR Immunity):** Every query accessing user assets enforces explicit ownership (`WHERE user_id = :session_user_id`).
4. **Dual-Mode Real-Time Notification Pipeline:** Native RFC 6455 WebSocket push daemon on port 8080 with seamless, automatic fallback to database polling (`realtime_events`).
5. **Headless Idempotent Daemon Execution:** Scheduled maintenance calculations run via restricted CLI commands guarded by file-locking (`flock`) and SHA-256 deduplication hashing.

---

## 2. Multi-Tier Application Structure

```text
+-----------------------------------------------------------------------------------+
|                            TIER 1: PRESENTATION LAYER                             |
|  - HTML5 / Bootstrap 5.3 Views (/dashboard, /vehicles, /services, /admin)        |
|  - Vanilla JS Async Clients (assets/js/notifications.js, assets/js/app.js)        |
|  - Output Sanitization (htmlspecialchars context-aware escaping)                  |
+-----------------------------------------------------------------------------------+
                                       │ HTTP GET / POST
                                       ▼
+-----------------------------------------------------------------------------------+
|                        TIER 2: ACTION & ROUTING CONTROLLERS                       |
|  - Root Entry & Direct Route Handlers (index.php, /actions/*, /admin/actions/*)   |
|  - Session Lifecycle & RBAC Guards (AuthHelper::requireRole, AuthHelper::guestOnly)|
|  - CSRF Token Validation & HTTP Method Enforcement                                |
+-----------------------------------------------------------------------------------+
                                       │ Validated Payload
                                       ▼
+-----------------------------------------------------------------------------------+
|                         TIER 3: DOMAIN & SERVICE LAYER                            |
|  - Maintenance Calculator Engine (MaintenanceCalculator::calculateStatus)         |
|  - Monotonic Mileage Advancement Coordinator                                      |
|  - Notification Deduplication Generator & Mail Dispatcher (MailHelper)            |
|  - Audit Trail Logging Service (AuditHelper::log)                                 |
+-----------------------------------------------------------------------------------+
                                       │ Business Objects / Prepared Parameters
                                       ▼
+-----------------------------------------------------------------------------------+
|                         TIER 4: DATA PERSISTENCE LAYER                            |
|  - Database Connection Singleton (DatabaseHelper::getInstance / PDO)              |
|  - Parameterized Query Repositories (Prepared Statements, 100% Parameter Binding) |
|  - Database Transactions (BEGIN TRANSACTION, COMMIT, ROLLBACK)                    |
+-----------------------------------------------------------------------------------+
                                       │ SQL / TCP Socket
                                       ▼
+-----------------------------------------------------------------------------------+
|                         TIER 5: STORAGE & DAEMON SERVICES                         |
|  - MariaDB 10.4 Relational Storage (schema.sql, versioned migrations)            |
|  - Ratchet RFC 6455 WebSocket Server (websocket/server.php on Port 8080)          |
|  - Headless Cron Daemon Runner (reminders/cron_reminders.php via CLI SAPI)        |
|  - File Attachment Storage (uploads/ with execution disabled)                     |
+-----------------------------------------------------------------------------------+
```

---

## 3. Communication Patterns

1. **Synchronous Web Requests (HTTP/HTTPS):**
   - Standard browser form submissions and page requests routed through Apache `.htaccess` to PHP view and action scripts.
   - Output rendered server-side using shared layouts (`includes/header.php`, `includes/sidebar.php`, `includes/footer.php`).
2. **Asynchronous Telemetry & Polling (AJAX / Fetch API):**
   - Lightweight AJAX endpoints (`/actions/fetch_notifications.php`, `/actions/mark_notification_read.php`) returning structured JSON payloads.
3. **Full-Duplex Real-Time Push (WebSocket):**
   - Persistent bidirectional WebSocket connection over `ws://localhost:8080` (or `wss://` in production).
   - Server-side events triggered via `WebSocketHelper::broadcast()` pushing instant updates to connected browser clients.

