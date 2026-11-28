# Architecture Decision Records (ADRs)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-ADR-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## Index of Architectural Decisions

| ADR ID | Decision Title | Status | Date |
|---|---|---|---|
| **ADR-001** | Existing PHP Application Architecture (Modular Native PHP) | Approved | 2026-11-05 |
| **ADR-002** | Authentication & Centralized RBAC Guard Architecture | Approved | 2026-11-05 |
| **ADR-003** | Database Ownership, Referential Integrity & Archival Deletion | Approved | 2026-11-05 |
| **ADR-004** | Versioned Incremental Database Migration Strategy | Approved | 2026-11-05 |
| **ADR-005** | Headless CLI Reminder Scheduler with Concurrency Locking | Approved | 2026-11-05 |
| **ADR-006** | Native RFC 6455 WebSocket Real-Time Integration with DB Fallback | Approved | 2026-11-05 |
| **ADR-007** | Immutable Administrative & System Audit Logging | Approved | 2026-11-05 |
| **ADR-008** | Application Routing Architecture (Direct PHP Scripts vs Framework Router) | Approved | 2026-11-05 |

---

### ADR-001: Existing PHP Application Architecture
* **Context:** The project is an existing functional web application built in native PHP 8+ without a heavyweight framework.
* **Problem:** Determine whether the existing modular structure is sufficient or whether migrating to an MVC framework (e.g. Laravel) is warranted.
* **Options Considered:**
  1. *Option A (Preserve Native Modular PHP):* Retain `/actions/*`, `/helpers/*`, `/includes/*`, and direct views.
  2. *Option B (Framework Migration):* Rebuild the entire platform on Laravel or CodeIgniter.
* **Decision:** **Option A**. Preserve and refine the existing modular native PHP architecture.
* **Rationale:** Complies with project guidelines forbidding greenfield rewrites. Native architecture requires zero runtime composer packages, operates with zero framework boot latency, and is easier to audit.
* **Consequences:** Developers must maintain discipline regarding separation of concerns, ensuring business logic resides in `/helpers/` and action handlers, not inside raw HTML templates.
* **Security & Performance Impact:** Minimal attack surface; sub-50 ms server response times.

---

### ADR-002: Authentication & Centralized RBAC Guard Architecture
* **Context:** The system requires authentication, session security, and access control separating `SYSTEM_ADMIN` and `VEHICLE_OWNER`.
* **Problem:** How to implement role validation cleanly without scattering ad-hoc `$_SESSION` checks across hundreds of files.
* **Options Considered:**
  1. *Option A (Centralized Guard Functions):* Enforce `requireLogin()`, `requireAdmin()`, `requireUser()`, `requireGuest()` in `includes/auth.php`.
  2. *Option B (Decentralized Ad-Hoc Checks):* Manual checks in every individual view.
* **Decision:** **Option A**. Centralize all authentication, session validation, and RBAC in `includes/auth.php`.
* **Rationale:** Eliminates DRY violations, guarantees consistent HTTP 403 Forbidden responses, and simplifies unit testing via mock sessions.
* **Consequences:** All controllers and action files must include `includes/auth.php` and invoke the appropriate guard as their first executable statement.

---

### ADR-003: Database Ownership, Referential Integrity & Archival Deletion
* **Context:** Vehicles, service records, schedules, and mileage logs have direct relational dependencies.
* **Problem:** How to handle vehicle deletion without triggering cascade deletion of lifetime maintenance expenses and invoices.
* **Options Considered:**
  1. *Option A (Hard SQL Delete with CASCADE):* `DELETE FROM vehicles WHERE id = ?`.
  2. *Option B (Soft-Archival Policy):* `UPDATE vehicles SET status = 'archived' WHERE id = ?`.
* **Decision:** **Option B**. Enforce soft-archival for vehicles; completed service records are permanently preserved and use auditable voiding (`status = 'Voided'`).
* **Rationale:** Automotive maintenance records serve as legal, financial, and safety documentation. Destructive deletion corrupts lifetime financial reports and resale provenance.
* **Consequences:** All active garage listings and dashboard count queries must filter `WHERE status != 'archived'`.

---

### ADR-004: Versioned Incremental Database Migration Strategy
* **Context:** Database schema evolves as new capabilities (deduplication keys, maintenance types, mileage logs) are introduced.
* **Problem:** How to track and apply database changes reliably across multiple team environments without overwriting data.
* **Options Considered:**
  1. *Option A (Incremental SQL Migrations):* Discrete, sequentially numbered SQL files in `database/migrations/`.
  2. *Option B (Monolithic Schema Overwrite):* Re-importing `database/schema.sql` repeatedly.
* **Decision:** **Option A**. Maintain `database/schema.sql` as the master fresh install script and commit discrete incremental `.sql` migrations in `database/migrations/`.
* **Rationale:** Allows existing production/development databases to be upgraded non-destructively without loss of user records.

---

### ADR-005: Headless CLI Reminder Scheduler with Concurrency Locking
* **Context:** Reminders must be evaluated and notifications generated on a predictable schedule independently of user browser requests.
* **Problem:** Evaluating reminders during HTTP requests degrades page load speed for active users; running cron jobs without concurrency guards risks race conditions.
* **Options Considered:**
  1. *Option A (Headless CLI Runner with Concurrency Lock):* Dedicated script `bin/process_reminders.php` utilizing `flock()` and executed via OS Cron / Task Scheduler.
  2. *Option B (HTTP Webhook Cron):* Exposing a public URL (`/cron/run.php`) to trigger background jobs.
* **Decision:** **Option A**. Restrict background reminder evaluation to a headless CLI runner guarded by non-blocking file concurrency locking (`flock`).
* **Rationale:** Prevents unauthorized external triggering, isolates long-running jobs from web server timeouts, and eliminates cron execution overlap.

---

### ADR-006: Native RFC 6455 WebSocket Real-Time Integration with DB Fallback
* **Context:** The system requires instant real-time alerting for vehicle owners and administrators.
* **Problem:** External SaaS services (Pusher) require subscription keys and internet access; standalone WebSocket socket connections may occasionally fail in sandboxed or firewalled developer environments.
* **Options Considered:**
  1. *Option A (Dual-Mode Native RFC 6455 + MySQL Event Queue):* Pure PHP streaming socket daemon (`websocket/server.php`) with persistent `realtime_events` fallback.
  2. *Option B (Third-Party SaaS Pusher/Socket.io):* Requires Node.js or cloud subscriptions.
  3. *Option C (HTTP Polling Only):* Creates heavy network traffic.
* **Decision:** **Option A**. Implement pure native RFC 6455 WebSocket streaming with database event queue fallback.
* **Rationale:** Zero external dependencies; operates offline; guarantees message delivery even when socket transport is restricted.

---

### ADR-007: Immutable Administrative & System Audit Logging
* **Context:** Security governance requires tracking administrative actions, role changes, and record mutations.
* **Problem:** Ensuring audit logs cannot be modified, cleared, or forged by compromised user or administrative sessions.
* **Options Considered:**
  1. *Option A (Immutable Append-Only Audit Table):* Schema without update/delete action routes; `user_id` set to NULL on account purge.
  2. *Option B (Text File Logging):* Writing to rotating log files.
* **Decision:** **Option A**. Relational `audit_logs` table with no edit or delete endpoints in the application code.
* **Rationale:** Supports structured SQL filtering (by actor, table, date range) while guaranteeing immutability across user sessions.

---

### ADR-008: Application Routing Architecture
* **Context:** The application contains web views, action POST handlers, and AJAX endpoints.
* **Problem:** Determine routing pattern: maintain direct PHP scripts with `.htaccess` protection or introduce a front-controller routing dispatcher.
* **Options Considered:**
  1. *Option A (Direct Script Endpoints with .htaccess Security):* Individual PHP files (e.g. `/vehicles/create.php`, `/actions/vehicle_action.php`).
  2. *Option B (Central Router Rewrite):* Rewrite all requests through `index.php?route=...`.
* **Decision:** **Option A**. Maintain direct script endpoints protected by centralized security headers and directory browsing rules in `.htaccess`.
* **Rationale:** Matches the existing codebase layout, avoids breaking existing relative links and forms, and provides transparent traceability between URLs and filesystem paths.

