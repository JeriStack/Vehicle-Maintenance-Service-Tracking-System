# Milestone Calendar & Project Roadmap
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-MC-001  
**Phase:** 0 — Project Initiation and Feasibility  
**Status:** Provisional Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Roadmap Overview

The VMS development lifecycle is organized into ten discrete, sequential milestones (**M0 through M9**). To accommodate academic schedules and semester durations without inventing fictitious calendar dates, this roadmap is structured on a **relative schedule** where progression is strictly gated by verified exit conditions.

---

## 2. Relative Milestone Schedule & Exit Gates

| Milestone | Title | Relative Duration | Major Deliverables | Dependencies | Exit Condition / Sign-Off Criteria |
|---|---|---|---|---|---|
| **M0** | **Project Initiation & Feasibility** | Week 1 | • Repository audit<br>• Project charter<br>• Environment baseline<br>• Decision & risk registers<br>• Phase 0 assessment | None | Phase 0 exit-gate review complete and verified by Project Lead. |
| **M1** | **Requirements Baseline** | Week 2 | • Software Requirements Specification (SRS)<br>• Two-role functional requirements<br>• Non-functional security & performance requirements | M0 | Formal sign-off on SRS; scope baseline confirmed. |
| **M2** | **System Architecture & Detailed Design** | Week 3 | • Entity-Relationship Diagram (ERD)<br>• UML Use-Case, Class, Sequence & Activity diagrams<br>• Data dictionary & schema design | M1 | Design documents reviewed against UML blueprints. |
| **M3** | **Technical Foundation & Security Hardening** | Week 4 | • Database schema migrations finalized<br>• Centralized RBAC and session hardening<br>• Zero-dependency test runner configured | M2 | All auth guards pass unit testing; zero unauthorized access vulnerabilities. |
| **M4** | **Core Vehicle & Service Records** | Week 5 | • Vehicle management with archival soft-delete<br>• Service logging & monotonic odometer updates<br>• Owner dashboard metrics | M3 | Live demonstration of Vehicle Owner CRUD workflows. |
| **M5** | **Smart Maintenance & Real-Time Alerting** | Week 6 | • Dual-criteria calculation engine<br>• Native WebSocket broadcast integration<br>• CLI reminder runner with process locking (`flock`) | M4 | 100% calculation test vectors pass; real-time alerts verified. |
| **M6** | **Administrative Oversight & Advanced Reporting** | Week 7 | • Admin user management & role guard verification<br>• Global maintenance type catalog<br>• Audit trail viewer<br>• CSV export & financial reports | M5 | Administrator demonstration complete; owner privacy preserved. |
| **M7** | **Quality Assurance & Security Auditing** | Week 8 | • Full test suite execution (`composer test`)<br>• IDOR penetration testing<br>• CSRF & XSS audit<br>• Input validation hardening | M6 | 100% automated test pass rate; zero critical security defects. |
| **M8** | **Release Preparation & Deployment Hardening** | Week 9 | • Deployment rehearsal via `SETUP_NOTES.txt`<br>• Apache `.htaccess` hardening<br>• Database backup & restoration drill<br>• Production configuration guide | M7 | Successful clean deployment on target environment in <= 15 minutes. |
| **M9** | **Final Demonstration & Project Handover** | Week 10 | • End-to-end stakeholder demonstration<br>• User manual & administrator guide<br>• Codebase archive & final submission | M8 | Formal acceptance by Project Sponsor / Course Instructor. |

---

## 3. Critical Path & Dependencies

```text
M0: Initiation ──► M1: Requirements ──► M2: Architecture & Design
                                                  │
                                                  ▼
M5: Automation ◄── M4: Core Workflows ◄── M3: Technical Foundation
       │
       ▼
M6: Admin & Reports ──► M7: QA & Security ──► M8: Deployment ──► M9: Final Acceptance
```

* **Primary Dependency Constraint:** No phase may begin implementation until the preceding phase's exit criteria have been documented and reviewed.
* **Provisional Notice:** Calendar dates remain provisional until formal academic term deadlines are established with the course instructor.

