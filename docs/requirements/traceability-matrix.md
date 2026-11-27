# Requirements Traceability Matrix (RTM)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-RTM-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Fully Traced  
**Last Updated:** 2026-11-05  

---

## 1. Traceability Overview

This matrix establishes bidirectional traceability linking every functional and nonfunctional requirement to its parent user story, acceptance criteria, test scenario, planned development sprint, and accountable owner.

---

## 2. Bidirectional Traceability Table

| Requirement ID | Requirement Title | User Role | Priority | Release | User Story ID | Acceptance Criteria ID | Test Scenario ID | Planned Sprint | Accountable Owner | Implementation Status |
|---|---|---|---|---|---|---|---|---|---|---|
| **AUTH-001** | User Registration | Guest | P0 | MVP | US-AUTH-01 | AC-AUTH-01 | TC-AUTH-01 | Sprint 1 | Backend Dev | IMPLEMENTED |
| **AUTH-002** | Secure Login | Guest | P0 | MVP | US-AUTH-02 | AC-AUTH-04 | TC-AUTH-03 | Sprint 1 | Backend Dev | IMPLEMENTED |
| **AUTH-003** | User Logout | Authenticated | P0 | MVP | US-AUTH-03 | AC-AUTH-07 | TC-AUTH-03 | Sprint 1 | Backend Dev | IMPLEMENTED |
| **AUTH-004** | Password Hashing (bcrypt) | System | P0 | MVP | US-AUTH-01 | AC-AUTH-01 | TC-AUTH-01 | Sprint 1 | Security Review | IMPLEMENTED |
| **AUTH-005** | Remember-Me Persistent Login | User / Admin | P0 | MVP | US-AUTH-04 | AC-AUTH-04 | TC-AUTH-06 | Sprint 1 | Backend Dev | IMPLEMENTED |
| **AUTH-006** | Self-Service Password Reset | Guest | P0 | MVP | US-AUTH-05 | AC-AUTH-01 | TC-AUTH-07 | Sprint 1 | Backend Dev | IMPLEMENTED |
| **AUTH-007** | Role-Based Access Control | System | P0 | MVP | US-AUTH-06 | AC-AUTH-03 | TC-AUTH-05 | Sprint 1 | Lead Architect | IMPLEMENTED |
| **AUTH-008** | Session Fixation Protection | System | P0 | MVP | US-AUTH-02 | AC-AUTH-04 | TC-AUTH-03 | Sprint 1 | Security Review | IMPLEMENTED |
| **AUTH-009** | CSRF Token Defense | System | P0 | MVP | US-AUTH-01 | AC-SEC-03 | TC-SEC-02 | Sprint 1 | Security Review | IMPLEMENTED |
| **VEH-001** | Register Personal Vehicle | Vehicle Owner | P0 | MVP | US-VEH-01 | AC-VEH-01 | TC-VEH-01 | Sprint 2 | Backend Dev | IMPLEMENTED |
| **VEH-002** | View Personal Garage | Vehicle Owner | P0 | MVP | US-VEH-02 | AC-VEH-01 | TC-VEH-01 | Sprint 2 | Frontend Dev | IMPLEMENTED |
| **VEH-003** | Update Vehicle Information | Vehicle Owner | P0 | MVP | US-VEH-03 | AC-VEH-05 | TC-VEH-03 | Sprint 2 | Backend Dev | IMPLEMENTED |
| **VEH-004** | Archive Vehicle (Soft-Delete) | Vehicle Owner | P0 | MVP | US-VEH-04 | AC-VEH-06 | TC-VEH-04 | Sprint 2 | Database Eng | PARTIALLY IMPLEMENTED |
| **VEH-005** | Vehicle IDOR Ownership Guard | System | P0 | MVP | US-VEH-02 | AC-VEH-07 | TC-VEH-02 | Sprint 2 | Security Review | IMPLEMENTED |
| **VEH-006** | Plate Normalization & Unique | System | P0 | MVP | US-VEH-01 | AC-VEH-02 | TC-VEH-01 | Sprint 2 | Backend Dev | PARTIALLY IMPLEMENTED |
| **VEH-007** | Odometer Audit History Log | System | P1 | Rel 1.1 | US-VEH-05 | AC-VEH-05 | TC-VEH-03 | Sprint 2 | Database Eng | PARTIALLY IMPLEMENTED |
| **SRV-001** | Record Completed Service | Vehicle Owner | P0 | MVP | US-SRV-01 | AC-SRV-01 | TC-SRV-01 | Sprint 3 | Backend Dev | IMPLEMENTED |
| **SRV-002** | View Service History | Vehicle Owner | P0 | MVP | US-SRV-02 | AC-SRV-01 | TC-SRV-01 | Sprint 3 | Frontend Dev | IMPLEMENTED |
| **SRV-003** | Edit Service Record | Vehicle Owner | P0 | MVP | US-SRV-03 | AC-SRV-01 | TC-SRV-01 | Sprint 3 | Backend Dev | IMPLEMENTED |
| **SRV-004** | Void Incorrect Service Record | Vehicle Owner | P1 | Rel 1.1 | US-SRV-04 | AC-SRV-04 | TC-SRV-01 | Sprint 3 | Database Eng | NOT IMPLEMENTED |
| **SRV-005** | Monotonic Odometer Progression| System | P0 | MVP | US-SRV-01 | AC-SRV-01 | TC-SRV-01 | Sprint 3 | Backend Dev | IMPLEMENTED |
| **SRV-006** | Provider & Parts Metadata | Vehicle Owner | P0 | MVP | US-SRV-01 | AC-SRV-01 | TC-SEC-01 | Sprint 3 | Frontend Dev | IMPLEMENTED |
| **SRV-007** | Monetary Decimal Precision | System | P0 | MVP | US-SRV-01 | AC-SRV-03 | TC-SRV-03 | Sprint 3 | Database Eng | IMPLEMENTED |
| **MTN-001** | Dual-Criteria Scheduling | Vehicle Owner | P0 | MVP | US-MTN-01 | AC-MTN-01 | TC-MTN-01 | Sprint 4 | Backend Dev | IMPLEMENTED |
| **MTN-002** | Status Calculation Engine | System | P0 | MVP | US-MTN-02 | AC-MTN-01 | TC-MTN-01 | Sprint 4 | Lead Architect | IMPLEMENTED |
| **MTN-003** | Upcoming Maintenance View | Vehicle Owner | P0 | MVP | US-MTN-03 | AC-MTN-05 | TC-MTN-04 | Sprint 4 | Frontend Dev | IMPLEMENTED |
| **MTN-004** | Due Maintenance Monitoring | Vehicle Owner | P0 | MVP | US-MTN-03 | AC-MTN-03 | TC-MTN-03 | Sprint 4 | Frontend Dev | IMPLEMENTED |
| **MTN-005** | Overdue Maintenance Alerting | Vehicle Owner | P0 | MVP | US-MTN-03 | AC-MTN-01 | TC-MTN-01 | Sprint 4 | Frontend Dev | IMPLEMENTED |
| **MTN-006** | Dedicated Schedules Entity | System | P1 | Rel 1.1 | US-MTN-04 | AC-MTN-01 | TC-MTN-01 | Sprint 4 | Database Eng | PARTIALLY IMPLEMENTED |
| **NTF-001** | In-App Notification Center | Vehicle Owner | P0 | MVP | US-NTF-01 | AC-NTF-02 | TC-NTF-01 | Sprint 5 | Frontend Dev | IMPLEMENTED |
| **NTF-002** | Maintenance Service Reminders | System | P0 | MVP | US-NTF-02 | AC-NTF-02 | TC-NTF-01 | Sprint 5 | Backend Dev | IMPLEMENTED |
| **NTF-003** | Registration Renewal Reminder | System | P0 | MVP | US-NTF-02 | AC-NTF-02 | TC-NTF-01 | Sprint 5 | Backend Dev | IMPLEMENTED |
| **NTF-004** | Idempotent Deduplication | System | P0 | MVP | US-NTF-03 | AC-NTF-01 | TC-NTF-01 | Sprint 5 | Database Eng | IMPLEMENTED |
| **NTF-005** | Real-Time WebSocket Alerts | User / Admin | P0 | MVP | US-NTF-04 | AC-NTF-02 | TC-NTF-02 | Sprint 5 | Backend Dev | IMPLEMENTED |
| **NTF-006** | Mark Notification Read/Delete | Vehicle Owner | P0 | MVP | US-NTF-01 | AC-NTF-02 | TC-NTF-01 | Sprint 5 | Frontend Dev | IMPLEMENTED |
| **NTF-007** | SMTP Email Notification Dispatch| System | P1 | Rel 1.1 | US-NTF-05 | AC-NTF-02 | TC-AUTH-07 | Sprint 5 | Backend Dev | PARTIALLY IMPLEMENTED |
| **DASH-001** | Vehicle Owner Dashboard | Vehicle Owner | P0 | MVP | US-DASH-01| AC-DASH-01 | TC-AUTH-03 | Sprint 6 | Frontend Dev | IMPLEMENTED |
| **DASH-002** | Real-Time Status Counters | Vehicle Owner | P0 | MVP | US-DASH-02| AC-DASH-01 | TC-NTF-02 | Sprint 6 | Frontend Dev | IMPLEMENTED |
| **DASH-003** | Workflow Shortcuts | Vehicle Owner | P0 | MVP | US-DASH-01| AC-DASH-01 | TC-AUTH-03 | Sprint 6 | Frontend Dev | IMPLEMENTED |
| **DASH-004** | Administrator Platform Dash | Admin | P0 | MVP | US-DASH-03| AC-ADM-01 | TC-ADM-01 | Sprint 6 | Frontend Dev | IMPLEMENTED |
| **RPT-001** | Maintenance Summary Report | Vehicle Owner | P0 | MVP | US-RPT-01 | AC-RPT-01 | TC-SRV-01 | Sprint 7 | Backend Dev | IMPLEMENTED |
| **RPT-002** | Chronological Service Log | Vehicle Owner | P0 | MVP | US-RPT-02 | AC-RPT-01 | TC-SRV-01 | Sprint 7 | Frontend Dev | IMPLEMENTED |
| **RPT-003** | Monthly & Yearly Cost Analysis| Vehicle Owner | P0 | MVP | US-RPT-03 | AC-RPT-01 | TC-SRV-03 | Sprint 7 | Frontend Dev | IMPLEMENTED |
| **RPT-004** | Vehicle-Specific Filtering | Vehicle Owner | P0 | MVP | US-RPT-01 | AC-RPT-01 | TC-VEH-02 | Sprint 7 | Backend Dev | IMPLEMENTED |
| **RPT-005** | RFC 4180 CSV Data Export | Vehicle Owner | P1 | Rel 1.1 | US-RPT-04 | AC-RPT-01 | TC-SRV-01 | Sprint 7 | Backend Dev | IMPLEMENTED |
| **SET-001** | User Profile Management | User / Admin | P0 | MVP | US-SET-01 | AC-SET-01 | TC-AUTH-01 | Sprint 5 | Backend Dev | IMPLEMENTED |
| **SET-002** | Account Password Change | User / Admin | P0 | MVP | US-SET-02 | AC-SET-01 | TC-AUTH-03 | Sprint 5 | Backend Dev | IMPLEMENTED |
| **SET-003** | Notification Preferences | Vehicle Owner | P0 | MVP | US-SET-03 | AC-NTF-03 | TC-NTF-01 | Sprint 5 | Backend Dev | IMPLEMENTED |
| **SET-004** | Vehicle Servicing Defaults | Vehicle Owner | P1 | Rel 1.1 | US-SET-04 | AC-SET-01 | TC-MTN-04 | Sprint 5 | Database Eng | IMPLEMENTED |
| **ADM-001** | User Directory & Search | Admin | P0 | MVP | US-ADM-01 | AC-ADM-01 | TC-ADM-01 | Sprint 7 | Frontend Dev | IMPLEMENTED |
| **ADM-002** | Account Activation / Deactivation| Admin | P0 | MVP | US-ADM-02 | AC-ADM-02 | TC-ADM-01 | Sprint 7 | Backend Dev | PARTIALLY IMPLEMENTED |
| **ADM-003** | Admin Privilege Management | Admin | P0 | MVP | US-ADM-03 | AC-ADM-03 | TC-ADM-01 | Sprint 7 | Backend Dev | IMPLEMENTED |
| **ADM-004** | Last-Administrator Protection | System | P0 | MVP | US-ADM-03 | AC-ADM-03 | TC-ADM-01 | Sprint 7 | Security Review | IMPLEMENTED |
| **ADM-005** | Global Service Type Catalog | Admin | P1 | Rel 1.1 | US-ADM-04 | AC-ADM-01 | TC-ADM-01 | Sprint 7 | Backend Dev | PARTIALLY IMPLEMENTED |
| **ADM-006** | Security Audit Log Viewer | Admin | P0 | MVP | US-ADM-05 | AC-ADM-03 | TC-ADM-01 | Sprint 7 | Frontend Dev | IMPLEMENTED |
| **ADM-007** | Owner Privacy Boundary | System | P0 | MVP | US-ADM-06 | AC-ADM-05 | TC-ADM-03 | Sprint 7 | Lead Architect | PARTIALLY IMPLEMENTED |
| **ADM-008** | System Settings Management | Admin | P1 | Rel 1.1 | US-ADM-07 | AC-ADM-01 | TC-ADM-01 | Sprint 7 | Backend Dev | PARTIALLY IMPLEMENTED |

---

## 3. Traceability Coverage & Gap Analysis

* **Orphaned Requirements:** `0` (100% of requirements map to an active User Story).
* **Stories without Requirements:** `0` (100% of stories trace back to authorized system requirements).
* **MVP Requirements without Acceptance Criteria:** `0` (100% of P0 requirements possess Given/When/Then acceptance criteria).
* **Acceptance Criteria without Test Scenarios:** `0` (100% of acceptance criteria map to test scenarios).
* **Sprint Allocation Coverage:** `100%` (All requirements are allocated across Sprints 1 through 7, with Sprint 8 dedicated to hardening and release verification).

