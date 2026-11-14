# Epic Breakdown & Hierarchy
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-EB-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Epic Overview

The VMS Product Backlog is partitioned into **nine core functional epics** corresponding directly to the approved system architecture:

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        VMS EPIC STRUCTURE                              │
├───────────┬────────────────────────────────────────┬───────────────────┤
│ Epic ID   │ Epic Name                              │ Target Role       │
├───────────┼────────────────────────────────────────┼───────────────────┤
│ EP-AUTH   │ Authentication & Account Security      │ Shared / System   │
│ EP-VEH    │ Vehicle Garage Management              │ Vehicle Owner     │
│ EP-SRV    │ Service Records & Maintenance History  │ Vehicle Owner     │
│ EP-MTN    │ Maintenance Tracking & Scheduling      │ Vehicle Owner     │
│ EP-NTF    │ Notifications & Real-Time Alerting     │ Shared / System   │
│ EP-DASH   │ Dashboards & System Summaries          │ Shared            │
│ EP-RPT    │ Reports, Analytics & Data Export       │ Vehicle Owner     │
│ EP-SET    │ Personal Settings & Preferences        │ Shared            │
│ EP-ADM    │ System Administration & Governance     │ System Admin      │
└───────────┴────────────────────────────────────────┴───────────────────┘
```

---

## 2. Detailed Epic Specifications

### Epic 1: `EP-AUTH` — Authentication & Account Security
* **Objective:** Deliver a secure, resilient authentication layer protecting user credentials, establishing roles, preventing session attacks, and providing self-service password recovery.
* **Requirements Covered:** `AUTH-001` through `AUTH-009`.
* **Primary Stories:**
  * `US-AUTH-01`: User Registration with bcrypt password hashing.
  * `US-AUTH-02`: Secure Login with session regeneration.
  * `US-AUTH-03`: Secure Logout and token invalidation.
  * `US-AUTH-04`: Remember-Me split-token persistent login.
  * `US-AUTH-05`: Self-Service "Forgot Password" token reset via SMTP mailer.
  * `US-AUTH-06`: Centralized Role-Based Access Control guards.
* **Completion Criteria:** All auth guards pass unit tests; session fixation and CSRF protection verified; zero credential leaks.

### Epic 2: `EP-VEH` — Vehicle Garage Management
* **Objective:** Enable vehicle owners to maintain an accurate inventory of personal vehicles while preventing cross-user record tampering and preserving historical data upon deletion.
* **Requirements Covered:** `VEH-001` through `VEH-007`.
* **Primary Stories:**
  * `US-VEH-01`: Register personal vehicle with plate normalization.
  * `US-VEH-02`: View and filter personal garage with status badges.
  * `US-VEH-03`: Update vehicle attributes and registration dates.
  * `US-VEH-04`: Soft-archive vehicles to preserve service invoices (`status = 'archived'`).
  * `US-VEH-05`: Odometer audit history logging.
* **Completion Criteria:** IDOR protection enforced on all vehicle routes; soft-archive retains dependent service records; plate normalization operational.

### Epic 3: `EP-SRV` — Service Records & Maintenance History
* **Objective:** Allow vehicle owners to catalog completed maintenance transactions, track part replacements, capture mechanic attribution, and automatically advance vehicle odometers.
* **Requirements Covered:** `SRV-001` through `SRV-007`.
* **Primary Stories:**
  * `US-SRV-01`: Log completed service with monotonic odometer advancement.
  * `US-SRV-02`: Filterable chronological service history viewer.
  * `US-SRV-03`: Edit service notes, costs, and replacement parts.
  * `US-SRV-04`: Void erroneous service records with mandatory justification reason.
* **Completion Criteria:** Future completed service dates rejected; decimal currency formatted with `₱`; master vehicle odometer advances monotonically.

### Epic 4: `EP-MTN` — Maintenance Tracking & Scheduling
* **Objective:** Dynamically project upcoming, due, and overdue maintenance requirements evaluated by date and mileage thresholds using the "whichever comes first" rule.
* **Requirements Covered:** `MTN-001` through `MTN-006`.
* **Primary Stories:**
  * `US-MTN-01`: Dual-criteria maintenance scheduling (target date and mileage).
  * `US-MTN-02`: Dynamic status calculation engine (`OVERDUE`, `DUE`, `UPCOMING`, `NOT_DUE`).
  * `US-MTN-03`: Dedicated status monitoring views for upcoming, due, and overdue tasks.
  * `US-MTN-04`: Decoupled future maintenance schedules entity (`maintenance_schedules`).
* **Completion Criteria:** 100% calculation accuracy verified in unit tests; completed service events close corresponding open schedules.

### Epic 5: `EP-NTF` — Notifications & Real-Time Alerting
* **Objective:** Alert vehicle owners to due maintenance and expiring registrations through in-app notification centers and instant RFC 6455 WebSocket broadcasts.
* **Requirements Covered:** `NTF-001` through `NTF-007`.
* **Primary Stories:**
  * `US-NTF-01`: In-app notification center with read/unread badge management.
  * `US-NTF-02`: Automated reminder generation for maintenance and registration renewal.
  * `US-NTF-03`: Idempotent deduplication preventing reminder spam.
  * `US-NTF-04`: Native RFC 6455 WebSocket real-time push with floating toasts and chime.
  * `US-NTF-05`: Automated SMTP email dispatch for critical overdue alerts.
* **Completion Criteria:** Background CLI script (`bin/process_reminders.php`) executes idempotently; WebSocket daemon delivers alerts <= 100 ms with DB queue fallback.

### Epic 6: `EP-DASH` — Dashboards & System Summaries
* **Objective:** Present role-specific dashboards surfacing high-priority action items, status counters, and operational shortcuts.
* **Requirements Covered:** `DASH-001` through `DASH-004`.
* **Primary Stories:**
  * `US-DASH-01`: Vehicle Owner personal dashboard with garage overview cards.
  * `US-DASH-02`: Real-time status counters updated via WebSocket push.
  * `US-DASH-03`: Administrator platform overview dashboard with system health indicators.
* **Completion Criteria:** Dashboards render in <= 200 ms; empty states handled gracefully; counters update live.

### Epic 7: `EP-RPT` — Reports, Analytics & Data Export
* **Objective:** Provide vehicle owners with deep financial insights into maintenance expenditures and exportable service history records.
* **Requirements Covered:** `RPT-001` through `RPT-006`.
* **Primary Stories:**
  * `US-RPT-01`: Vehicle maintenance summary report.
  * `US-RPT-02`: Filterable chronological service log with cost totals.
  * `US-RPT-03`: Monthly and yearly expenditure trends with Chart.js visual charts.
  * `US-RPT-04`: RFC 4180-compliant CSV export engine (`reports/export_csv.php`).
* **Completion Criteria:** Cost queries execute owner-scoped; CSV output handles special characters, commas, and multiline text cleanly.

### Epic 8: `EP-SET` — Personal Settings & Preferences
* **Objective:** Empower users to manage their profiles, change passwords, and configure personalized notification lead times and servicing defaults.
* **Requirements Covered:** `SET-001` through `SET-004`.
* **Primary Stories:**
  * `US-SET-01`: User profile name and phone number management.
  * `US-SET-02`: Secure password change verifying current password.
  * `US-SET-03`: Notification preference toggles and reminder lead days.
  * `US-SET-04`: Default vehicle servicing intervals configuration.
* **Completion Criteria:** Password updates re-hash credentials; invalid lead times (< 1 or > 30 days) rejected.

### Epic 9: `EP-ADM` — System Administration & Governance
* **Objective:** Equip system administrators with user lifecycle controls, audit logging, and catalog management while enforcing the owner privacy boundary.
* **Requirements Covered:** `ADM-001` through `ADM-008`.
* **Primary Stories:**
  * `US-ADM-01`: User directory with full-text search.
  * `US-ADM-02`: Account activation and deactivation toggle.
  * `US-ADM-03`: Administrator role management with last-admin protection.
  * `US-ADM-04`: Global maintenance service type catalog curation (`maintenance_types`).
  * `US-ADM-05`: Security audit log viewer.
  * `US-ADM-06`: Administrator privacy boundary enforcement restricting private vehicle records.
* **Completion Criteria:** Demotion of sole administrator blocked; all administrative mutations logged; private owner service notes hidden from administrator accounts.

