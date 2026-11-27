# Vehicle Maintenance Service Tracking System (VMS)
## Engineering & Governance Documentation Index

Welcome to the central documentation repository for the **Vehicle Maintenance Service Tracking System (VMS)**. All project documentation is organized by functional domain across **Phases 0–6**.

---

## 1. Governance (`docs/governance/`)

| Document | Description |
|---|---|
| [**Project Charter**](governance/project-charter.md) | Business problem, two-role model, core goals, acceptance criteria, and constraints |
| [**Scope Statement**](governance/scope-statement.md) | Approved MVP scope, feature inclusions, explicit exclusions, and role boundaries |
| [**Environment Baseline**](governance/environment-baseline.md) | Primary runtime environment (XAMPP/Windows), verified PHP/MariaDB/Apache versions, and extensions |
| [**Decision Register**](governance/decision-register.md) | Technical and architectural decisions (`D-001`–`D-011`) with rationale and status |
| [**Risk Register**](governance/risk-register.md) | Risk assessment matrix (`R-001`–`R-010`) covering security, integrity, and mitigations |
| [**Stakeholder Register**](governance/stakeholder-register.md) | Project stakeholder matrix, roles, responsibilities, and engagement levels |
| [**Communication Plan**](governance/communication-plan.md) | Sprint review cadence, escalations, and documentation channels |
| [**Milestone Calendar**](governance/milestone-calendar.md) | Lifecycle milestone roadmap (`M0`–`M9`) mapped to exit gates |

---

## 2. Requirements & Backlog (`docs/requirements/`, `docs/backlog/`)

### Requirements Specifications
| Document | Description |
|---|---|
| [**Requirements Baseline**](requirements/requirements-baseline.md) | Authoritative 58-requirement baseline across 9 categories (`AUTH`, `VEH`, `SRV`, `MTN`, `NTF`, `DASH`, `RPT`, `SET`, `ADM`) |
| [**Release Scope & Reconciliation**](requirements/release-scope.md) | Release boundary definitions (`P0`/`P1`/`P2`) and scope reconciliation decisions |
| [**Business Rules & Validation Catalogue**](requirements/business-rules-validation-catalogue.md) | 26 formal business rules, validation constraints, and security invariants |
| [**Acceptance Criteria**](requirements/acceptance-criteria.md) | End-to-end Given/When/Then acceptance criteria across user journeys and security |
| [**Initial Test Scenarios**](requirements/initial-test-scenarios.md) | 25 detailed test scenarios covering positive, negative, and security vectors |
| [**Traceability Matrix (RTM)**](requirements/traceability-matrix.md) | Bidirectional Requirements Traceability Matrix linking requirements, stories, and tests |

### Product Backlog & Roadmap
| Document | Description |
|---|---|
| [**Definition of Ready & Done**](backlog/definition-of-ready.md) | Formal engineering entry (`DoR`) and completion (`DoD`) quality standards |
| [**Epic Breakdown**](backlog/epic-breakdown.md) | Nine core epic specifications with goals, scope, and completion criteria |
| [**Product Backlog (Markdown)**](backlog/product-backlog.md) | 37 user stories, 84 tasks, and 135 story points |
| [**Product Backlog (CSV)**](backlog/product-backlog.csv) | RFC 4180 CSV backlog ready for spreadsheet/issue-tracker import |
| [**Sprint Allocation**](backlog/sprint-allocation.md) | Sprint roadmap mapping MVP points across Sprints 1–8 |

---

## 3. Architecture, Routing & UML (`docs/architecture/`)

| Document | Description |
|---|---|
| [**Application Architecture**](architecture/application-architecture.md) | 5-tier native PHP architecture, core pillars, and dual-mode real-time event pipeline |
| [**Architecture Baseline**](architecture/architecture-baseline.md) | Measurable performance SLAs, capacity limits, and integrity invariants |
| [**Architecture Decision Records**](architecture/architecture-decision-records.md) | Formal ADRs (`ADR-001`–`ADR-008`) |
| [**Component Responsibilities**](architecture/component-responsibilities.md) | Single Responsibility Principle assignments across all application layers |
| [**Directory Structure & PSR-4**](architecture/directory-structure.md) | Repository directory layout, PSR-4 namespaces, and `.htaccess` security invariants |
| [**Request Lifecycle**](architecture/request-lifecycle.md) | 11-stage deterministic request execution pipeline |
| [**Process Analysis**](architecture/process-analysis.md) | Process architecture across `GUEST`, `VEHICLE_OWNER`, `SYSTEM_ADMIN`, and `CLI_SCHEDULER` |
| [**Workflow Validation**](architecture/workflow-validation.md) | Audit of VMS System Workflow against requirements and discrepancy register |
| [**Application Route Map**](architecture/routing/route-map.md) | Inventory of application routes across views, actions, AJAX, WebSocket, and CLI |
| [**Authorization Matrix**](architecture/routing/authorization-matrix.md) | Access control matrix and server-side sensitive operation guards |
| [**UML Diagram Pack**](architecture/uml/) | 12 PlantUML source models (Use Case, Activity, Sequence, Domain Class, Component, ERD) |

---

## 4. Database (`docs/database/`)

### Logical & Conceptual Design (`docs/database/design/`)
| Document | Description |
|---|---|
| [**Architectural ERD**](database/design/erd.md) | Logical Entity-Relationship Diagram and ownership hierarchy |
| [**Data Dictionary**](database/design/data-dictionary.md) | Complete table/column types, nullability, defaults, keys, and validations |
| [**Relationship Catalogue**](database/design/relationship-catalogue.md) | Relational cardinalities, optionality, and cascade specifications |
| [**Constraints & Indexes**](database/design/constraints-and-indexes.md) | Composite lookup indexes and uniqueness constraints |
| [**Lifecycle & Deletion Rules**](database/design/lifecycle-and-deletion-rules.md) | Soft-archival, auditable voiding, and immutable audit log rules |
| [**State Transition Matrix**](database/design/state-transition-matrix.md) | Permitted entity states and transitions |

### Physical Implementation & Operations (`docs/database/implementation/`)
| Document | Description |
|---|---|
| [**Schema Implementation**](database/implementation/schema-implementation.md) | Physical InnoDB / `utf8mb4` schema implementation details |
| [**Migration Inventory**](database/implementation/migration-inventory.md) | Ordered migration registry, execution order, and dependency mapping |
| [**ERD Verification**](database/implementation/erd-verification.md) | Physical ERD verification matrix against live schema |
| [**Foreign Key Catalogue**](database/implementation/foreign-key-catalogue.md) | Verified foreign key constraint names and referential actions |
| [**Index Catalogue**](database/implementation/index-catalogue.md) | Physical B-tree index inventory and query optimization rationale |
| [**Normalization & Constraints**](database/implementation/normalization-and-constraints.md) | Email normalization, owner-scoped plate uniqueness, and deduplication keys |
| [**Seed Data Specification**](database/implementation/seed-data.md) | Standardized maintenance catalog seeders and idempotency rules |
| [**Rollback & Forward-Fix Policy**](database/implementation/rollback-and-forward-fix-policy.md) | DDL rollback and forward-fix operational policy |
| [**Clean Installation Guide**](database/implementation/clean-installation-guide.md) | Fresh database setup, migration, and seeding runbook |
| [**Existing Database Upgrade Guide**](database/implementation/existing-database-upgrade.md) | Non-destructive upgrade procedure preserving existing data |

---

## 5. Security (`docs/security/`)

| Document | Description |
|---|---|
| [**STRIDE Threat Model**](security/threat-model.md) | Assets, trust boundaries, threats, mitigations, and residual risks |
| [**Security Controls Matrix**](security/security-controls-matrix.md) | Technical security controls mapped to concrete implementations |
| [**Authentication Implementation**](security/authentication-implementation.md) | Public registration, password hashing policy, email normalization, and generic errors |
| [**Authorization Policy (RBAC)**](security/authorization-policy.md) | Two-role model, middleware guards, and IDOR ownership isolation |
| [**Session Management**](security/session-management.md) | Hardened cookies, regeneration, idle timeout, and `auth_version` invalidation |
| [**CSRF Protection**](security/csrf-protection.md) | 64-char CSPRNG tokens, constant-time validation, and rotation |
| [**Admin Provisioning (CLI)**](security/admin-provisioning.md) | One-time CLI bootstrap via `php bin/console admin:create` |

---

## 6. UI/UX & Wireframes (`docs/ui-ux/`)

| Document | Description |
|---|---|
| [**Design Token System**](ui-ux/design-tokens.md) | Color palette, spacing scales, and typography tokens |
| [**Component Inventory**](ui-ux/component-inventory.md) | Shared UI components, states, and accessibility specs |
| [**Navigation Architecture**](ui-ux/navigation-structure.md) | Role-specific menu structures for Vehicle Owner and System Administrator |
| [**UI Implementation Checklist**](ui-ux/ui-implementation-checklist.md) | Pre-commit UI quality and WCAG 2.1 AA accessibility checklist |
| [**Authentication Wireframes**](ui-ux/wireframes/authentication/authentication-wireframes.md) | Desktop & mobile wireframes for Login, Register, Forgot & Reset Password |
| [**Vehicle Owner Wireframes**](ui-ux/wireframes/owner/owner-wireframes.md) | Desktop & mobile wireframes for Dashboard, Garage, Services, Schedules, Reports |
| [**Administrator Wireframes**](ui-ux/wireframes/admin/admin-wireframes.md) | Desktop & mobile wireframes for Governance Dashboard, Users, Catalog, Audit Logs |
| [**Shared Component Wireframes**](ui-ux/wireframes/shared/shared-wireframes.md) | Wireframes for Topbar, Sidebar, Notifications, Modals, and Toasts |

---

## 7. Engineering Standards (`docs/standards/`)

| Document | Description |
|---|---|
| [**Coding Conventions**](standards/coding-conventions.md) | PHP 8+ strict typing, PSR-12 formatting, and PHPDoc standards |
| [**Naming Conventions**](standards/naming-conventions.md) | Standard naming for classes, methods, tables, migrations, and routes |
| [**Error Handling Conventions**](standards/error-handling.md) | HTTP status codes, structured JSON errors, and stack-trace shielding |
| [**Logging Standards**](standards/logging-standards.md) | Structured logging levels, channels, and secret redaction |
| [**Testing Standards**](standards/testing-standards.md) | Unit, smoke, and integration testing conventions |
| [**Migration Standards**](standards/migration-standards.md) | Timestamp naming, idempotency, and rollback conventions |
| [**Code Review Checklist**](standards/code-review-checklist.md) | Pull-request audit gate across security, quality, and tests |

---

## 8. Phase Exit-Gate Assessments (`docs/assessments/`)

| Document | Description |
|---|---|
| [**Phase 0 Assessment**](assessments/phase-0-assessment.md) | Repository audit, feasibility analysis, and Phase 0 exit gate |
| [**Phase 1 Assessment**](assessments/phase-1-assessment.md) | Requirements confirmation and backlog baseline readiness report |
| [**Phase 2 Assessment**](assessments/phase-2-assessment.md) | Process analysis and technical design exit-gate report |
| [**Phase 3 Assessment**](assessments/phase-3-assessment.md) | UI/UX planning and architecture baseline exit-gate report |
| [**Phase 4 Assessment**](assessments/phase-4-assessment.md) | Repository and application foundation (Sprint 1) completion report |
| [**Phase 5 Assessment**](assessments/phase-5-assessment.md) | Database schema, migrations, and seed data exit-gate report |
