# Workflow Validation Report & Gap Analysis
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-WV-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS System Workflow (7 pages), Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Executive Summary

This report documents the formal validation of the existing **VMS System Workflow** against the approved Phase 1 Requirements Baseline (`docs/project/requirements/requirements-baseline.md`). 

Every workflow was audited to identify discrepancies between the theoretical workflow diagrams, legacy codebase behaviors, and the approved architectural invariants.

---

## 2. Workflow Validation Matrix

| Workflow Area | Step in Original Diagram | Requirement IDs | Current Implementation Behavior | Identified Gap / Discrepancy | Technical Design Resolution | Approval Status |
|---|---|---|---|---|---|---|
| **WF-AUTH-01** | User Self-Registration | `AUTH-001`, `AUTH-004`, `AUTH-009` | Form in `auth/register.php`; inserts record with `role = 'user'`. | Form lacked email normalization (`strtolower()`) and case-insensitive check in DB constraint. | Add canonical normalization and case-insensitive unique constraint in schema design. | Approved |
| **WF-AUTH-02** | User Login & Routing | `AUTH-002`, `AUTH-007`, `AUTH-008` | Authenticates via `actions/login_action.php`; routes by role. | Remember-me split token and autofill dark glass styling were missing in early workflow. | Fully integrated split-token architecture (`checkRememberMe()`) and autofill CSS. | Approved |
| **WF-AUTH-03** | Password Recovery | `AUTH-006` | Reset request in `auth/forgot_password.php`. | Original workflow lacked offline development fallback for email delivery. | Implemented dual-mode mailer (`helpers/mail_helper.php`): live SMTP or `logs/mail.log`. | Approved |
| **WF-VEH-01** | Vehicle Registration | `VEH-001`, `VEH-006` | Form in `vehicles/create.php`. | Plate uniqueness was unconstrained, allowing identical plates across multiple garage inputs. | Enforce owner-scoped canonical plate uniqueness (`BR-VEH-02`). | Approved |
| **WF-VEH-02** | Vehicle Deletion | `VEH-004` | Action executes SQL `DELETE FROM vehicles`. | **Critical Discrepancy:** Hard SQL delete triggers cascading deletion of historical service records. | Transition deletion to soft-archival (`UPDATE vehicles SET status = 'archived'`). | Approved |
| **WF-VEH-03** | Odometer History | `VEH-003`, `VEH-007` | Edit updates `vehicles.current_mileage`. | Odometer history log table was missing in original schema. | Introduce `vehicle_mileage_logs` entity to log each odometer update transactionally. | Approved |
| **WF-SRV-01** | Service Logging | `SRV-001`, `SRV-005` | Form in `services/create.php`; updates odometer. | Completed service date permitted future dates; future schedule was conflated with completed service. | Invariant `service_date <= CURRENT_DATE`; decouple future targets into `maintenance_schedules`. | Approved |
| **WF-SRV-02** | Service Invalidation | `SRV-004` | Service deletion was hard SQL delete. | Erroneous service entry deletion corrupted past expenditure totals without audit history. | Implement auditable voiding workflow (`status = 'Voided'`) with mandatory reason. | Approved |
| **WF-MTN-01** | Status Calculation | `MTN-001`, `MTN-002` | Logic embedded in inline SQL queries across pages. | Inconsistent threshold logic and lack of unit testability. | Isolated calculation engine (`helpers/maintenance_calculator.php`) with 8 automated test vectors. | Approved |
| **WF-NTF-01** | Scheduled Reminders | `NTF-002`, `NTF-004` | Reminders evaluated on page load in `includes/header.php`. | Page load evaluation misses inactive users; risks duplicate alert spamming on refresh. | Headless CLI processor (`bin/process_reminders.php`) with `flock` and deduplication keys. | Approved |
| **WF-NTF-02** | Real-Time Push | `NTF-005` | WebSocket server daemon and client notifications. | Direct TCP socket client failed in sandbox environments with restricted local network. | Implemented dual-mode event delivery: direct TCP socket + `realtime_events` MySQL queue. | Approved |
| **WF-ADM-01** | Admin Role Demotion | `ADM-003`, `ADM-004` | Action in `actions/admin_user_action.php`. | Risk of self-demotion or removing the last administrator, causing administrative lockout. | Server-side guards blocking self-demotion and last active administrator demotion. | Approved |
| **WF-ADM-02** | Admin Privacy Boundary | `ADM-007` | Admin views list all private vehicles and services across users. | **Critical Discrepancy:** Exposes private vehicle maintenance and invoices to administrative accounts. | Refactor admin views to display platform health and anonymized aggregations. | Approved |
| **WF-ADM-03** | Account Deactivation | `ADM-002` | `users.status` checked during authentication. | UI toggle button missing from administrator user management view. | Add status toggle action and UI controls in `admin/user_view.php`. | Approved |

---

## 3. Discrepancy Register & Resolution Actions

### Discrepancy 1: Vehicle Hard Delete vs. Soft Archival
* **Nature:** Architecture & Data Integrity Discrepancy.
* **Impact:** Destructive deletion violates financial reporting integrity and destroys lifetime maintenance history.
* **Resolution:** Formally approved. In Phase 4, `actions/vehicle_action.php` executes `UPDATE vehicles SET status = 'archived' WHERE id = ? AND user_id = ?`.

### Discrepancy 2: Completed Service Invoices vs. Future Maintenance Schedules
* **Nature:** Relational Modeling Discrepancy.
* **Impact:** Storing future target dates inside completed invoice rows creates confusion when calculating recurring schedules.
* **Resolution:** Formally approved. Future maintenance schedules are decoupled into the dedicated entity `maintenance_schedules`, referencing `vehicles` and `maintenance_types`.

### Discrepancy 3: Administrator Access to Private Owner Records
* **Nature:** Security & Privacy Boundary Discrepancy.
* **Impact:** Unrestricted administrator access violates user privacy expectations outlined in Section 3.3 of the System Plan.
* **Resolution:** Formally approved. Administrators manage platform accounts, global service catalogs, and audit logs. Access to individual owner vehicles is bounded.

