# Vehicle Maintenance Service Tracking System (VMS)
## Project Engineering & Governance Documentation

Welcome to the central project governance and engineering repository for the **Vehicle Maintenance Service Tracking System (VMS)**.

This directory contains the formal deliverables, technical specifications, requirements baselines, architectural models, and backlog planning established during **Phase 0 (Initiation & Feasibility)**, **Phase 1 (Requirements Confirmation & Backlog Baseline)**, and **Phase 2 (Process Analysis & Technical Design)**.

---

## 1. Phase 0 — Project Initiation & Feasibility

| Document | Description | Status |
|---|---|---|
| [**Project Charter**](project-charter.md) | Business problem, two-role model, core goals, measurable acceptance criteria, constraints, and demonstration outcomes | Complete |
| [**Scope Statement**](scope-statement.md) | Formally approved MVP scope, feature inclusions, explicit exclusions, and user role boundaries | Complete |
| [**Environment Baseline**](environment-baseline.md) | Primary runtime environment (XAMPP/Windows), verified PHP/MariaDB/Apache versions, required extensions, and regional defaults | Complete |
| [**Decision Register**](decision-register.md) | Register of technical and architectural decisions (D-001 through D-011) with options, rationale, and status | Complete |
| [**Risk Register**](risk-register.md) | Risk assessment matrix (R-001 through R-010) covering security, data integrity, calculations, and mitigations | Complete |
| [**Stakeholder Register**](stakeholder-register.md) | Project stakeholder matrix, roles, responsibilities, and engagement levels | Complete |
| [**Communication Plan**](communication-plan.md) | Cadence for sprint reviews, risk escalations, demonstration gates, and documentation channels | Complete |
| [**Milestone Calendar**](milestone-calendar.md) | Relative milestone roadmap (M0 through M9) mapping the complete development lifecycle to exit gates | Complete |
| [**Phase 0 Assessment**](phase-0-assessment.md) | Comprehensive repository audit, implementation inventory, technical gap analysis, and exit gate report | Complete |

---

## 2. Phase 1 — Requirements Confirmation & Backlog Baseline

### A. Requirements Specifications (`docs/project/requirements/`)

| Document | Description | Status |
|---|---|---|
| [**Requirements Baseline**](requirements/requirements-baseline.md) | Authoritative 58-requirement baseline across 9 categories (`AUTH`, `VEH`, `SRV`, `MTN`, `NTF`, `DASH`, `RPT`, `SET`, `ADM`) | Complete |
| [**Release Scope & Reconciliation**](requirements/release-scope.md) | Release boundary definitions (P0/P1/P2) and scope reconciliation decisions | Complete |
| [**Business Rules & Validation Catalogue**](requirements/business-rules-validation-catalogue.md) | 26 formal business rules, validation constraints, and security invariants | Complete |
| [**Acceptance Criteria**](requirements/acceptance-criteria.md) | End-to-end Given/When/Then acceptance criteria for Journeys A through G + Security | Complete |
| [**Initial Test Scenarios**](requirements/initial-test-scenarios.md) | 25 detailed test scenarios covering positive, negative, and security vectors | Complete |
| [**Traceability Matrix (RTM)**](requirements/traceability-matrix.md) | Bidirectional Requirements Traceability Matrix linking requirements, stories, and tests | Complete |

### B. Product Backlog & Roadmap (`docs/project/backlog/`)

| Document | Description | Status |
|---|---|---|
| [**Definition of Ready & Done**](backlog/definition-of-ready.md) | Formal engineering entry (DoR) and completion (DoD) quality standards | Complete |
| [**Epic Breakdown**](backlog/epic-breakdown.md) | Nine core epic specifications with goals, scope, and completion criteria | Complete |
| [**Product Backlog (Markdown)**](backlog/product-backlog.md) | Complete backlog containing 37 user stories, 84 tasks, and 135 story points | Complete |
| [**Product Backlog (CSV)**](backlog/product-backlog.csv) | Spreadsheet-compatible RFC 4180 CSV backlog ready for Jira/DevOps import | Complete |
| [**Sprint Allocation**](backlog/sprint-allocation.md) | Sprint roadmap mapping 105 MVP points across Sprints 1 to 8 | Complete |
| [**Phase 1 Assessment**](phase-1-assessment.md) | Phase 1 exit-gate assessment and readiness report | Complete |

---

## 3. Phase 2 — Process Analysis & Technical Design

### A. Business Processes, UML Models & Architecture (`docs/project/design/`)

| Document | Description | Status |
|---|---|---|
| [**Process Analysis**](design/process-analysis.md) | Complete process architecture across 4 execution contexts (`GUEST`, `VEHICLE_OWNER`, `SYSTEM_ADMIN`, `CLI_SCHEDULER`) | Complete |
| [**Workflow Validation**](design/workflow-validation.md) | Formal audit of VMS System Workflow against requirements; discrepancy register | Complete |
| [**Architecture Overview**](design/architecture-overview.md) | 5-tier architecture overview and core pillars (zero framework churn, IDOR immunity) | Complete |
| [**Architecture Decision Records**](design/architecture-decision-records.md) | Formal ADRs ADR-001 through ADR-008 | Complete |
| [**UML Diagram Pack**](design/uml/) | 12 PlantUML source models (Use Case, Activity, Sequence, Domain Class, Component, ERD) | Complete |

### B. Logical & Physical Database Design (`docs/project/design/database/`)

| Document | Description | Status |
|---|---|---|
| [**Architectural ERD**](design/database/erd.md) | Architectural Entity-Relationship Diagram and ownership hierarchy | Complete |
| [**Data Dictionary**](design/database/data-dictionary.md) | Complete 10-table data dictionary detailing types, nullability, defaults, keys, and validations | Complete |
| [**Relationship Catalogue**](design/database/relationship-catalogue.md) | Relational map detailing all 12 foreign keys, cardinalities, and cascade actions | Complete |
| [**Constraints & Indexes**](design/database/constraints-and-indexes.md) | Index design for high-frequency search queries, composite lookups, and constraints | Complete |
| [**Lifecycle & Deletion Invariants**](design/database/lifecycle-and-deletion-rules.md) | Rules for soft-archival (`status = 'archived'`), auditable voiding, and immutable audit logs | Complete |
| [**State Transition Matrix**](design/database/state-transition-matrix.md) | State models and permitted transitions for users, vehicles, services, and schedules | Complete |

### C. Application Routing & Security (`docs/project/design/routing/`, `docs/project/design/security/`)

| Document | Description | Status |
|---|---|---|
| [**Application Route Map**](design/routing/route-map.md) | Complete inventory of 48 application routes across web views, actions, AJAX, WebSocket, and CLI | Complete |
| [**Authorization Matrix**](design/routing/authorization-matrix.md) | Complete access control matrix and explicit server-side sensitive operation guards | Complete |
| [**STRIDE Threat Model**](design/security/threat-model.md) | 14 assets, 8 trust boundaries, 14 threats, mitigations, tests, and residual risks | Complete |
| [**Security Controls Matrix**](design/security/security-controls-matrix.md) | Technical security controls mapping requirements to concrete implementations | Complete |
| [**Phase 2 Assessment**](phase-2-assessment.md) | Phase 2 exit-gate assessment and technical design baseline report | Complete |

---

## 4. Core Architectural Principles

1. **Two-Role Model:** Exactly two system roles exist: `SYSTEM_ADMIN` and `VEHICLE_OWNER`. Mechanics and service centers are descriptive metadata attributes on service transactions, **not** authenticated user roles.
2. **Strict Data Boundary:** Administrators oversee user accounts, global catalog maintenance types, audit logs, and system health. Administrators **do not** view private owner service histories without explicit user consent.
3. **Data Integrity Over Deletion:** Historical records and service invoices must be preserved. Vehicle deletion is an archival operation (`status = 'archived'`) to maintain financial and maintenance integrity.
4. **Zero-Dependency Native Architecture:** Native PHP 8+, PDO MySQL, and Bootstrap 5 without bloated third-party frameworks, maximizing maintainability, auditability, and ease of deployment.
