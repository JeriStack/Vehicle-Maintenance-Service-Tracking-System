# Project Scope Statement
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-SS-001  
**Phase:** 0 — Project Initiation and Feasibility  
**Scope Baseline Status:** Formally Defined / Pending Final Stakeholder Sign-Off  
**Last Updated:** 2026-11-05  

---

## 1. Project Purpose & Scope Overview

The scope of the **Vehicle Maintenance Service Tracking System (VMS)** encompasses the design, implementation, testing, and deployment of a full-featured web application dedicated to automotive maintenance management, registration monitoring, and administrative governance.

This document formally delineates what is **IN SCOPE** for the MVP (Minimum Viable Product), what is explicitly **OUT OF SCOPE**, the functional boundaries separating the two user roles, and the foundational technical dependencies.

---

## 2. In-Scope MVP Modules & Features

The following capabilities constitute the approved Minimum Viable Product (MVP):

### 2.1 Authentication & Session Security Module
- User registration with input validation (full name, email, password, contact number).
- Password security via native `password_hash()` (bcrypt) and `password_verify()`.
- Multi-channel login supporting standard session authentication.
- Secure split-token "Remember Me" persistent cookie functionality (`selector` + `hashed_validator`).
- Secure self-service "Forgot Password" workflow generating 60-minute SHA-256 tokens and email dispatch (via native RFC 5321 socket SMTP client with offline log fallback).
- Session fixation protection via `session_regenerate_id(true)` upon privilege transition.
- CSRF token generation and validation across all state-mutating POST/PUT/DELETE forms.
- Centralized authentication and role guards (`requireLogin()`, `requireGuest()`, `requireAdmin()`, `requireUser()`).

### 2.2 Two-Role Access Control (RBAC) Module
- **`SYSTEM_ADMIN` Role:** Access restricted to `/admin/*` routes; governance of platform user accounts, global maintenance catalog, audit trail reviews, and system configuration.
- **`VEHICLE_OWNER` Role:** Access restricted to personal garage (`/vehicles/*`), service logging (`/services/*`), maintenance schedules (`/maintenance/*`), personal alerts (`/notifications/*`, `/reminders/*`), and personal reports (`/reports/*`).

### 2.3 Vehicle Garage Management Module
- Create, Read, Update personal vehicle records.
- Track plate number, vehicle type, make, model, model year, color, VIN number, registration date, registration expiration date, and current odometer reading.
- **Archival Deletion Policy:** Deleting a vehicle sets its status to `archived` to safeguard historical service invoices and financial summaries from cascade deletion.
- Enforce strict owner-scoped queries (`WHERE user_id = ?`) preventing IDOR cross-user access.

### 2.4 Service Records & Maintenance History Module
- Create and edit completed maintenance entries (service type, service date, odometer reading at service, cost in PHP ₱, service provider, parts replaced, detailed notes).
- Monotonic odometer enforcement: Updating mileage via service logging updates the vehicle's master odometer if greater than the prior reading.
- Permanent service log history with filterable search (by date, vehicle, and service type).

### 2.5 Smart Maintenance Scheduling Engine
- Dynamic calculation engine implementing the dual-criteria "whichever comes first" maintenance rules:
  - **`OVERDUE`:** Scheduled date is in the past OR current odometer exceeds target mileage.
  - **`DUE`:** Scheduled date is today OR current odometer equals target mileage.
  - **`UPCOMING`:** Scheduled date is within the configured reminder window (e.g., 7–30 days) OR current odometer is within 500–1,000 km of target.
  - **`NOT_DUE`:** Date and mileage are outside active reminder thresholds.
- Dedicated status views: `/maintenance/upcoming.php`, `/maintenance/due.php`, `/maintenance/overdue.php`.

### 2.6 Automated Notifications & Real-Time Alerting Engine
- In-app notification center tracking service reminders and registration expiration warnings.
- Background CLI reminder processor (`bin/process_reminders.php`) equipped with file concurrency locks (`flock`) and deduplication keys (`dedupe_key`) preventing reminder duplicate spam.
- Real-time notification broadcaster using native RFC 6455 WebSockets with instant floating UI toasts and audio chimes, backed by a MySQL database event queue for network resilience.
- User-configurable reminder thresholds (lead days and notification toggles).

### 2.7 Reports & Financial Analytics Module
- Personal owner dashboards featuring real-time overview metrics and charts (Chart.js).
- Maintenance summary reports, monthly cost breakdowns, and yearly expenditure analyses.
- Data export engine generating RFC 4180-compliant CSV files (`/reports/export_csv.php`).

### 2.8 Administrative Oversight & Security Audit Trail
- Administrator dashboard displaying aggregate platform metrics (total users, active vehicles, completed services).
- User directory with search and role management capabilities (with guards blocking self-demotion and last-administrator demotion).
- Global maintenance service type catalog allowing administrators to curate standard maintenance items.
- Centralized security audit log (`audit_logs` table) tracking logins, role changes, and critical record alterations.

### 2.9 Testing & Quality Assurance
- Zero-dependency automated test runner and assertion framework (`tests/run_tests.php`, `tests/TestCase.php`) executable via `composer test`.
- Comprehensive test coverage for authentication, validation, date helpers, mailers, websocket frames, and maintenance calculation algorithms.
- GitHub Actions CI workflow pipeline verifying syntax and database schema on push/pull requests.

---

## 3. Explicit Scope Exclusions (Out of Scope for Initial MVP)

The following items are **explicitly excluded** from the current project scope and must not be implemented during the initial MVP. They are documented here to prevent scope creep:

| Excluded Feature | Classification | Rationale for Exclusion |
|---|---|---|
| **Mechanic / Service-Provider Logins** | Future Expansion | Mechanics are descriptive invoice fields only; introducing mechanic accounts violates the approved two-role RBAC blueprint. |
| **Online Payment Gateway (Stripe/PayPal/GCash)** | Future Expansion | VMS is an informational tracking system, not an ecommerce checkout platform; maintenance costs are recorded as completed personal expenditures. |
| **GPS / Real-Time Telematics Tracking** | Future Expansion | Requires hardware OBD-II dongles or continuous cellular IoT streams, exceeding the standard web application scope. |
| **Native Mobile App (iOS / Android)** | Future Expansion | The responsive Bootstrap 5 web interface serves mobile viewport requirements without cross-compilation overhead. |
| **SMS Gateway Integration** | Future Expansion | Avoids third-party telecommunications carrier costs (e.g. Twilio/Semaphore); in-app notifications and SMTP email suffice for MVP. |
| **Vehicle Marketplace / Buy & Sell Board** | Excluded | Unrelated to core maintenance service tracking and owner record-keeping. |
| **Insurance Claim Portal** | Excluded | Third-party insurance carrier integration is outside academic project boundaries. |
| **Multi-Tenant Commercial SaaS Billing** | Excluded | MVP targets individual vehicle owners and single-institution administrative management. |

---

## 4. User-Role Boundaries

```text
┌──────────────────────────────────────┬─────────────────┬──────────────────┐
│ Capability / Route                   │  SYSTEM_ADMIN   │  VEHICLE_OWNER   │
├──────────────────────────────────────┼─────────────────┼──────────────────┤
│ Administrative Dashboard (/admin/*)  │     ALLOWED     │      DENIED      │
│ Manage User Roles & Accounts         │     ALLOWED     │      DENIED      │
│ Manage Global Maintenance Catalog    │     ALLOWED     │      DENIED      │
│ View Security Audit Logs             │     ALLOWED     │      DENIED      │
│ View System Health Metrics           │     ALLOWED     │      DENIED      │
├──────────────────────────────────────┼─────────────────┼──────────────────┤
│ Owner Personal Dashboard (/dashboard)│      DENIED     │     ALLOWED      │
│ Register & Manage Personal Vehicles  │      DENIED     │     ALLOWED      │
│ Log Personal Maintenance & Invoices  │      DENIED     │     ALLOWED      │
│ View Personal Reminders & Schedules  │      DENIED     │     ALLOWED      │
│ Export Personal Expense CSV Reports  │      DENIED     │     ALLOWED      │
│ View Other Users' Private Vehicles   │      DENIED     │      DENIED      │
└──────────────────────────────────────┴─────────────────┴──────────────────┘
```

---

## 5. Scope Assumptions & Project Dependencies

### 5.1 Assumptions
1. **Single Deployment Context:** The system is hosted in a standard local or virtualized environment (XAMPP / Apache / MariaDB).
2. **Odometer Accuracy:** Vehicle owners manually input odometer readings honestly and accurately when adding vehicles or recording service events.
3. **Connectivity:** Users possess modern web browsers with WebSocket and JavaScript support enabled.

### 5.2 Dependencies
1. **PHP Runtime:** PHP 8.0+ with PDO MySQL, Session, OpenSSL, and JSON extensions enabled.
2. **Database Engine:** MySQL 8.0+ or MariaDB 10.4+ supporting InnoDB, foreign keys, and transactions.
3. **Web Server:** Apache 2.4+ with `mod_rewrite` and `mod_headers` enabled via `.htaccess`.
4. **WebSocket Port:** TCP port `8080` (or configured port) accessible for local loopback streaming.
5. **Operating System:** Windows development environment with cross-platform compatibility for Linux deployment.

