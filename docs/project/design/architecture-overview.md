# Application Architecture Overview
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-AO-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Architectural Style & Design Principles

The Vehicle Maintenance Service Tracking System (VMS) is architected as a **Modular Native PHP Application** adhering to the **Separation of Concerns (SoC)** principle across five distinct conceptual tiers:

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        VMS MULTI-TIER ARCHITECTURE                     │
├────────────────────────────────────────────────────────────────────────┤
│ 1. Presentation Tier: Responsive Bootstrap 5 Views, Glassmorphism CSS, │
│    Vanilla JavaScript, Chart.js, and WebSocket Push Handlers           │
├────────────────────────────────────────────────────────────────────────┤
│ 2. Controller & Action Tier: Direct PHP Endpoints, Action Handlers     │
│    (/actions/*), Form Processing, and HTTP Status Codes                │
├────────────────────────────────────────────────────────────────────────┤
│ 3. Security & Guard Tier: Centralized Authentication Guards            │
│    (requireLogin, requireAdmin), CSRF Defense, and Session Hardening   │
├────────────────────────────────────────────────────────────────────────┤
│ 4. Domain & Helper Tier: Pure Calculation Services (MaintenanceCalc),  │
│    Notification Broadcasters (WebSocketHelper), and Mailers (MailHelper)│
├────────────────────────────────────────────────────────────────────────┤
│ 5. Persistence Tier: Parameterized PDO Prepared Statements, MariaDB /  │
│    MySQL InnoDB Engine with Foreign Keys and Transactions              │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Core Architectural Pillars

### 2.1 Zero-Dependency Native Core
The application runtime does not depend on heavy third-party PHP frameworks (e.g. Laravel, Symfony) or Composer packages. Core functions—including session handling, cryptographic password verification, RFC 5321 socket SMTP email transmission, RFC 6455 WebSocket streaming, and CSV generation—are implemented using native PHP 8+ and standard system extensions. Composer is utilized strictly for unit testing (`composer test`).

### 2.2 Dual-Mode Real-Time Notification Architecture
To guarantee notification delivery across diverse deployment environments (including sandboxed terminal containers where local TCP port binding may be restricted), the platform implements **Dual-Mode Delivery**:
1. *Fast Direct Broadcast:* Non-blocking local TCP socket streaming to port 8080.
2. *Persistent Database Queue:* Instant insertion into `realtime_events` MySQL queue, consumed by the background daemon every loop iteration.

### 2.3 Strict Data Isolation (IDOR Immunity)
All database interactions concerning vehicles, service invoices, schedules, and mileage histories require traversal of the owner relationship (`WHERE user_id = ?`). Attempting to request or manipulate records belonging to another owner fails server-side with `HTTP 403 Forbidden`.

### 2.4 Administrative Privacy Boundary
Administrative accounts possess platform governance capabilities (user activation, role assignment, service catalog curation, audit log review), but **are strictly prohibited from viewing private owner vehicle repair histories or invoices**.

