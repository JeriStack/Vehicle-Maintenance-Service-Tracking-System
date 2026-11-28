# Definition of Ready (DoR) and Definition of Done (DoD)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DORD-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Approved Engineering Standard  
**Last Updated:** 2026-11-05  

---

## 1. Definition of Ready (DoR)

A backlog item (User Story or Technical Task) is formally classified as **READY** to be pulled into an active development sprint only when it satisfies all of the following entry conditions:

### Entry Checklist:
- [ ] **Unique Identifier:** Item has a permanent ID (e.g. `US-VEH-01`, `TASK-VEH-01`).
- [ ] **Parent Epic & Requirement:** Item is mapped to an official Epic and Requirement ID in the Requirements Traceability Matrix (RTM).
- [ ] **User Role Identified:** Explicitly states the acting role (`VEHICLE_OWNER`, `SYSTEM_ADMIN`, or `System`).
- [ ] **Clear User Story & Business Value:** Follows the canonical format: *As a [role], I want [capability], So that [benefit]*.
- [ ] **Priority & Release Assigned:** Classified unambiguously as `P0 (MVP)`, `P1 (Rel 1.1)`, or `P2 (Optional)`.
- [ ] **Testable Acceptance Criteria:** Formulated in Given / When / Then format with positive, negative, and boundary scenarios.
- [ ] **Dependencies Identified:** All prerequisite schemas, helpers, and prior stories are documented.
- [ ] **Validation & Security Invariants Defined:** Input validation constraints, sanitization rules, and authorization boundaries are documented.
- [ ] **Story Point Estimate:** Estimated using the Fibonacci sequence (1, 2, 3, 5, 8, 13) by the engineering team.
- [ ] **Accountable Owner Assigned:** An engineering role (e.g., Backend Dev, Security Review) is assigned accountability.
- [ ] **Right-Sized Scope:** Can be implemented and verified within a single sprint iteration (<= 8 story points; 13-point items must be decomposed).

> [!CAUTION]
> Any story with unresolved technical unknowns, missing schema definitions, or ambiguous authorization rules must remain tagged as **`BLOCKED`** or **`NOT READY`**.

---

## 2. Definition of Done (DoD)

A story or task is formally classified as **DONE** and eligible for sprint review credit only when all of the following completion criteria are verified:

### Completion Checklist:
- [ ] **Acceptance Criteria Satisfied:** All Given / When / Then criteria pass functional verification.
- [ ] **Server-Side Authorization Enforced:** Role guards (`requireAdmin()`, `requireUser()`) and owner-scoped queries (`WHERE user_id = ?`) prevent IDOR and unauthorized privilege escalation.
- [ ] **Input Sanitization & Output Escaping:** All inputs validated; string outputs rendered with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [ ] **SQL Parameterization:** Zero string-interpolated queries; all queries execute via PDO prepared statements.
- [ ] **Database Integrity & Migrations:** Any schema modifications use committed, versioned SQL migration scripts in `database/migrations/`.
- [ ] **Automated Tests Passing:** Relevant unit or integration tests are added/updated; the automated test suite (`composer test`) passes with **100% success and 0 failures**.
- [ ] **Zero Regressions:** Existing core application functionality, dashboards, and navigation remain unbroken.
- [ ] **No Secrets Exposed:** Zero hardcoded credentials, production tokens, or private keys committed to Git.
- [ ] **Documentation Updated:** Code comments maintained; user manuals, setup notes, or API docs updated to reflect changes.
- [ ] **Verified on Primary Environment:** Verified running natively on the primary development environment (XAMPP / Apache / Windows).

