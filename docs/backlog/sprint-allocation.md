# Sprint Allocation & Roadmap Plan
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-SA-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Sprint Allocation Methodology

The VMS development roadmap spans **eight planned sprints** structured strictly by architectural dependency order. Foundational security, user roles, and database tables precede core CRUD, which precedes automation, dashboards, reporting, and final hardening.

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        SPRINT ROADMAP MATRIX                           │
├───────────┬────────────────────────────────────────┬───────────────────┤
│ Sprint    │ Focus Area                             │ MVP Points        │
├───────────┼────────────────────────────────────────┼───────────────────┤
│ Sprint 1  │ Foundation, Authentication & RBAC      │ 25 pts            │
│ Sprint 2  │ Vehicle Garage Management              │ 11 pts            │
│ Sprint 3  │ Service Records & History              │ 11 pts            │
│ Sprint 4  │ Maintenance Scheduling & Status Calc   │ 11 pts            │
│ Sprint 5  │ Notifications, Real-Time WS & Settings │ 19 pts            │
│ Sprint 6  │ Owner & Administrator Dashboards       │ 9 pts             │
│ Sprint 7  │ System Administration & Reporting      │ 19 pts            │
│ Sprint 8  │ Security Hardening, QA & Release Prep  │ Hardening / Gates │
├───────────┴────────────────────────────────────────┼───────────────────┤
│ Total MVP (P0) Story Points Allocated              │ 105 pts           │
└────────────────────────────────────────────────────┴───────────────────┘
```

---

## 2. Detailed Sprint Allocations

### Sprint 1: Foundation, Authentication & RBAC
* **Sprint Goal:** Establish a rock-solid, secure user authentication system with split-token persistent logins, self-service password recovery, and strict two-role RBAC guards.
* **Included User Stories:**
  * `US-AUTH-01`: User Registration (5 pts)
  * `US-AUTH-02`: Secure Login & Session Establishment (5 pts)
  * `US-AUTH-03`: User Logout & Session Invalidation (2 pts)
  * `US-AUTH-04`: Remember-Me Persistent Login (5 pts)
  * `US-AUTH-05`: Self-Service Password Reset (5 pts)
  * `US-AUTH-06`: Centralized RBAC Route Guards (3 pts)
* **Total Sprint Points:** **25 pts** (P0: 25)
* **Dependencies:** Database schema initialized (`database/schema.sql`).
* **Deliverables:** Functional `/auth/*` pages, `includes/auth.php` guards, native SMTP mailer (`helpers/mail_helper.php`), and automated auth test suite.
* **Exit Conditions:** All auth unit tests pass; zero role escalation; CSRF and session fixation defenses verified.

### Sprint 2: Vehicle Garage Management
* **Sprint Goal:** Deliver complete personal vehicle garage management with plate canonicalization, owner-scoped queries, and soft-archival deletion.
* **Included User Stories:**
  * `US-VEH-01`: Register Personal Vehicle (3 pts)
  * `US-VEH-02`: View & Search Personal Garage (2 pts)
  * `US-VEH-03`: Update Vehicle Details & Odometer (3 pts)
  * `US-VEH-04`: Archive Vehicle (Soft-Delete) (3 pts)
  * `US-VEH-05`: Odometer Audit History Log [P1] (3 pts)
* **Total Sprint Points:** **14 pts** (P0: 11, P1: 3)
* **Dependencies:** Sprint 1 (`US-AUTH-06` RBAC guards).
* **Deliverables:** Functional `/vehicles/*` management, soft-delete action in `actions/vehicle_action.php`, and owner-scoped IDOR protection.
* **Exit Conditions:** Cross-user vehicle access blocked; soft-archiving preserves historical records.

### Sprint 3: Service Records & Service History
* **Sprint Goal:** Enable vehicle owners to log maintenance transactions, advance vehicle odometers monotonically, and browse historical service logs.
* **Included User Stories:**
  * `US-SRV-01`: Log Completed Service Record (5 pts)
  * `US-SRV-02`: View Service History Log (3 pts)
  * `US-SRV-03`: Edit Completed Service Record (3 pts)
  * `US-SRV-04`: Void Erroneous Service Record [P1] (3 pts)
* **Total Sprint Points:** **14 pts** (P0: 11, P1: 3)
* **Dependencies:** Sprint 2 (`US-VEH-01` Vehicle entity).
* **Deliverables:** Functional `/services/*` management, transactional vehicle odometer updates, and sanitized provider/part notes.
* **Exit Conditions:** Future completed service dates rejected; decimal currency formatted cleanly; vehicle odometer increases monotonically.

### Sprint 4: Maintenance Tracking & Scheduling
* **Sprint Goal:** Implement the dual-criteria maintenance status calculation engine using the "whichever comes first" algorithm and deliver status monitoring views.
* **Included User Stories:**
  * `US-MTN-01`: Dual-Criteria Maintenance Scheduling (3 pts)
  * `US-MTN-02`: Dynamic Status Calculation Engine (5 pts)
  * `US-MTN-03`: Dedicated Status Monitoring Views (3 pts)
  * `US-MTN-04`: Dedicated Maintenance Schedules Entity [P1] (5 pts)
* **Total Sprint Points:** **16 pts** (P0: 11, P1: 5)
* **Dependencies:** Sprint 3 (`US-SRV-01` Service logging).
* **Deliverables:** `helpers/maintenance_calculator.php` domain service, `/maintenance/upcoming.php`, `due.php`, `overdue.php` views, and calculation unit test suite.
* **Exit Conditions:** 8/8 calculation test vectors pass with 100% precision.

### Sprint 5: Notifications, Real-Time WebSockets & Settings
* **Sprint Goal:** Deliver automated in-app notifications, background CLI reminder processing with deduplication, and native RFC 6455 real-time push alerts.
* **Included User Stories:**
  * `US-NTF-01`: In-App Notification Center (3 pts)
  * `US-NTF-02`: Automated Reminders Processor (5 pts)
  * `US-NTF-03`: Idempotent Reminder Deduplication (3 pts)
  * `US-NTF-04`: Real-Time WebSocket Alerts (8 pts)
  * `US-NTF-05`: Automated SMTP Email Alerting [P1] (3 pts)
  * `US-SET-01`: User Profile Management (2 pts)
  * `US-SET-02`: Account Password Change (3 pts)
  * `US-SET-03`: Notification Preferences Configuration (3 pts)
  * `US-SET-04`: Default Vehicle Servicing Preferences [P1] (2 pts)
* **Total Sprint Points:** **32 pts** (P0: 27, P1: 5)
* **Dependencies:** Sprint 4 (`US-MTN-02` Calculation engine).
* **Deliverables:** `bin/process_reminders.php` CLI runner, `websocket/server.php` daemon, `assets/js/notifications.js`, and `/settings/*` portal.
* **Exit Conditions:** Repeated cron runs produce zero duplicate rows; WebSocket push delivers toasts in <= 100 ms with DB queue fallback.

### Sprint 6: Owner & Administrator Dashboards
* **Sprint Goal:** Provide high-impact visual dashboards for both vehicle owners and system administrators, featuring live counters and workflow shortcuts.
* **Included User Stories:**
  * `US-DASH-01`: Vehicle Owner Personal Dashboard (3 pts)
  * `US-DASH-02`: Real-Time Status Counters (3 pts)
  * `US-DASH-03`: Administrator Platform Overview Dashboard (3 pts)
* **Total Sprint Points:** **9 pts** (P0: 9)
* **Dependencies:** Sprints 2, 3, 4, 5.
* **Deliverables:** `/dashboard/index.php`, `/admin/dashboard.php`, and live counter update handlers.
* **Exit Conditions:** Dashboard renders in <= 200 ms; empty states handled; live counters update upon service creation.

### Sprint 7: System Administration & Reporting
* **Sprint Goal:** Equip administrators with user lifecycle management and audit logs, enforce the owner privacy boundary, and provide vehicle owners with financial reports and CSV exports.
* **Included User Stories:**
  * `US-RPT-01`: Maintenance Summary Report (3 pts)
  * `US-RPT-02`: Chronological Service History Log (3 pts)
  * `US-RPT-03`: Monthly & Yearly Expenditure Analytics (5 pts)
  * `US-RPT-04`: RFC 4180 CSV Data Export [P1] (3 pts)
  * `US-ADM-01`: User Directory & Search (3 pts)
  * `US-ADM-02`: Account Activation / Deactivation (3 pts)
  * `US-ADM-03`: Role Management with Last-Admin Guard (5 pts)
  * `US-ADM-04`: Global Service Type Catalog [P1] (5 pts)
  * `US-ADM-05`: Security Audit Log Viewer (3 pts)
  * `US-ADM-06`: Owner Privacy Boundary Enforcement (5 pts)
  * `US-ADM-07`: System-Wide Configuration Management [P1] (3 pts)
* **Total Sprint Points:** **41 pts** (P0: 27, P1: 14)
* **Dependencies:** Sprints 1 through 6.
* **Deliverables:** `/reports/*` analytics suite with Chart.js, `/admin/users.php`, `/admin/audit_logs.php`, and privacy boundary enforcement.
* **Exit Conditions:** Last active administrator cannot be demoted; administrators cannot browse private owner repair notes; CSV export validates.

### Sprint 8: Security Hardening, QA & Release Preparation
* **Sprint Goal:** Conduct end-to-end security penetration testing, IDOR validation, cross-browser styling verification, and deployment rehearsal.
* **Included Activities:**
  * Comprehensive test suite execution (`composer test` - 44+ unit tests).
  * Static analysis and PHP 8.0 / 8.5 syntax verification.
  * SQL injection, XSS, and CSRF penetration testing.
  * Clean deployment rehearsal following `SETUP_NOTES.txt`.
  * Database backup and restore verification.
* **Exit Conditions:** Zero critical security defects; 100% automated test pass rate; deployment rehearsal succeeds in <= 15 minutes.

