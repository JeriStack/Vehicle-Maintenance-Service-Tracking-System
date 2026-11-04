# Vehicle Maintenance Service Tracking System (VMS)
## Project Engineering & Governance Documentation

Welcome to the central project governance and engineering repository for the **Vehicle Maintenance Service Tracking System (VMS)**.

This directory contains the formal deliverables, technical specifications, requirements baselines, architectural models, and backlog planning established during **Phase 0 (Initiation & Feasibility)**, **Phase 1 (Requirements Confirmation & Backlog Baseline)**, **Phase 2 (Process Analysis & Technical Design)**, **Phase 3 (UI/UX Planning & Architecture Baseline)**, **Phase 4 (Repository and Application Foundation)**, and **Phase 5 (Database Schema, Migrations, and Seed Data)**.

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

## 4. Phase 3 — UI/UX Planning & Architecture Baseline

### A. Design System & Layout Specifications (`docs/project/design/ui-ux/`)

| Document | Description | Status |
|---|---|---|
| [**Wireframe Inventory**](design/ui-ux/wireframe-inventory.md) | Authoritative catalogue of 19 screens and shared layout shells | Complete |
| [**Wireframe Specifications**](design/ui-ux/wireframe-specifications.md) | Layout geometry, functional zones, modal dialogues, and tabular representations | Complete |
| [**Responsive Multi-Device Behavior**](design/ui-ux/responsive-behavior.md) | Breakpoint rules, table-to-card transformations, off-canvas drawers, touch targets | Complete |
| [**Design Token System**](design/ui-ux/design-tokens.md) | Approved Navy, Teal, White, Light-Gray palette, spacing scales, and typography | Complete |
| [**Component Inventory**](design/ui-ux/component-inventory.md) | Shared UI components, properties, operational states, and accessibility specs | Complete |
| [**Navigation Architecture**](design/ui-ux/navigation-structure.md) | Role-specific menu trees for Owner and Administrator with server-side enforcement | Complete |
| [**UI Implementation Checklist**](design/ui-ux/ui-implementation-checklist.md) | Mandatory pre-commit quality and WCAG 2.1 AA accessibility audit checklist | Complete |

### B. Wireframe Suite (`docs/project/design/wireframes/`)

| Document | Description | Status |
|---|---|---|
| [**Authentication Wireframes**](design/wireframes/authentication/authentication-wireframes.md) | Desktop & mobile wireframes for Login, Register, Forgot Password, Reset Password | Complete |
| [**Vehicle Owner Wireframes**](design/wireframes/owner/owner-wireframes.md) | Desktop & mobile wireframes for Dashboard, Garage, Services, Schedules, Reports | Complete |
| [**Administrator Wireframes**](design/wireframes/admin/admin-wireframes.md) | Desktop & mobile wireframes for Governance Dashboard, Users, Catalog, Audit Logs | Complete |
| [**Shared Component Wireframes**](design/wireframes/shared/shared-wireframes.md) | Wireframes for Topbar, Sidebar, Notification Drawer, Modals, Toasts, Skeletons | Complete |

### C. Application Architecture Baseline (`docs/project/design/architecture/`)

| Document | Description | Status |
|---|---|---|
| [**Application Architecture**](design/architecture/application-architecture.md) | 5-tier architecture, native PHP core, and dual-mode real-time event pipeline | Complete |
| [**Request Lifecycle**](design/architecture/request-lifecycle.md) | 11-stage deterministic request execution pipeline from routing to view render | Complete |
| [**Directory Structure & PSR-4**](design/architecture/directory-structure.md) | Existing module mapping, root entry preservation, and target `app/` hierarchy | Complete |
| [**Component Responsibilities**](design/architecture/component-responsibilities.md) | Single Responsibility Principle (SRP) assignments across all 7 layers | Complete |
| [**Architecture Baseline**](design/architecture/architecture-baseline.md) | Performance SLAs (TTFB < 150ms, DB < 25ms, WS < 80ms) and operational limits | Complete |

### D. Development Standards (`docs/project/design/standards/`)

| Document | Description | Status |
|---|---|---|
| [**Coding Conventions**](design/standards/coding-conventions.md) | PHP 8+ strict typing, PSR-12 formatting, explicit method signatures, PHPDoc | Complete |
| [**Naming Conventions**](design/standards/naming-conventions.md) | Standardized naming for classes, methods, database elements, migrations, routes | Complete |
| [**Error Response Conventions**](design/standards/error-handling.md) | HTML status codes (400-500), structured JSON error responses, leakage defense | Complete |
| [**Logging Standards**](design/standards/logging-standards.md) | Structured JSON logging, RFC 5424 levels, dedicated channels, secret redaction | Complete |
| [**Testing Standards**](design/standards/testing-standards.md) | PHPUnit conventions, categorization, rollback database isolation, coverage | Complete |
| [**Migration Standards**](design/standards/migration-standards.md) | Timestamp naming (`YYYYMMDDHHMMSS_{desc}.sql`), idempotency, rollback scripts | Complete |
| [**Code Review Checklist**](design/standards/code-review-checklist.md) | 20-point engineering pull-request audit gate across security, quality, tests | Complete |
| [**Phase 3 Assessment**](phase-3-assessment.md) | Phase 3 exit-gate assessment and architecture baseline report | Complete |

---

## 5. Phase 4 — Repository and Application Foundation (Sprint 1)

### Technical Foundation & Core Infrastructure (`app/`, `bin/`, `public/`, `storage/`)

| Deliverable | Description | Status |
|---|---|---|
| [**Public Web Document Root**](../../public/) | Dedicated `public/` web root containing `index.php`, `.htaccess`, and `assets/`, isolating code and data from web exposure | Complete |
| [**Composer PSR-4 Autoloading**](../../composer.json) | Clean namespace separation with `"VMS\\": "app/"`, `"Database\\Seeders\\": "database/seeders/"`, and helper auto-inclusion | Complete |
| [**Centralized Configuration & Env**](../../app/Core/Config.php) | Secure `.env` environment loading and type-safe centralized `Config::get()` access without hardcoded secrets | Complete |
| [**Hardened PDO Database Manager**](../../app/Core/Database.php) | Centralized PDO singleton with UTF-8 (`utf8mb4`), exception mode, and connection failure logging | Complete |
| [**Front Controller & Router**](../../app/Core/Router.php) | Native RESTful router supporting dynamic parameters (`/vehicles/{id}`), middleware execution, 404, and 405 handlers | Complete |
| [**Secure Session & Flash Engine**](../../app/Core/Session.php) | Hardened session bootstrap with strict mode, HTTP-only cookie protection, Lax SameSite, and atomic flash messaging | Complete |
| [**Cryptographic CSRF Protection**](../../app/Core/Csrf.php) | 64-character CSPRNG CSRF token generator, hidden input helper, and timing-attack-safe validation via `hash_equals()` | Complete |
| [**Application Centralized Error Handler**](../../app/Core/App.php) | Centralized PHP error and exception trap preventing sensitive stack traces from leaking to clients | Complete |
| [**CLI Migration & Seeder Console**](../../bin/console) | Console utility (`bin/console`) supporting `migrate`, `migrate:status`, `seed`, `health`, and `help` | Complete |
| [**System Health Monitoring Endpoints**](../../app/Controllers/HealthController.php) | Public, non-sensitive liveness probe (`GET /health`) and database readiness probe (`GET /health/ready`) | Complete |
| [**Automated Smoke Test Suite**](../../tests/Smoke/SmokeTest.php) | 12 automated smoke tests verifying bootstrap, routing, CSRF, sessions, migrations, and health endpoints | Complete |
| [**Phase 4 Assessment Report**](phase-4-assessment.md) | Comprehensive Phase 4 and Sprint 1 foundation execution report and completion assessment | Complete |

---

## 6. Phase 5 — Database Schema, Migrations, and Seed Data (Sprint 1)

### Relational Engineering & Migration Deliverables (`database/`, `docs/project/database/`)

| Deliverable | Description | Status |
|---|---|---|
| [**Migration Inventory**](database/migration-inventory.md) | Full registry of all 13 ordered migrations, execution order, affected tables, and dependency mapping | Complete |
| [**Schema Implementation**](database/schema-implementation.md) | Physical table schema specifications, InnoDB storage engine, and utf8mb4 encoding across all 13 tables | Complete |
| [**ERD Verification**](database/erd-verification.md) | Mermaid ERD model, relationship verification matrix, and cardinalities | Complete |
| [**Foreign Key Catalogue**](database/foreign-key-catalogue.md) | Complete referential integrity catalogue, 15 foreign key constraints, deletion rules, and archival policies | Complete |
| [**Index Catalogue**](database/index-catalogue.md) | Full index inventory, B-tree query optimizations, composite keys, and selectivity rationale | Complete |
| [**Normalization & Constraints**](database/normalization-and-constraints.md) | Canonical email normalization, owner-scoped plate uniqueness (`BR-VEH-02`), and dedupe key algorithms | Complete |
| [**Seed Data Specification**](database/seed-data.md) | Standardized maintenance catalog (8 items) with stable codes, slug routing, and idempotency rules | Complete |
| [**Rollback & Forward-Fix Policy**](database/rollback-and-forward-fix-policy.md) | Relational DDL commit rules, reversible rollback operations, and forward-fix protocols | Complete |
| [**Clean Installation Guide**](database/clean-installation-guide.md) | Scenario A installation sequence from an empty database to fully migrated/seeded state | Complete |
| [**Existing Database Upgrade Guide**](database/existing-database-upgrade.md) | Scenario B safe upgrade procedure for pre-existing databases with 100% record preservation | Complete |
| [**Database Integration Tests**](../../tests/Integration/Database/DatabaseIntegrationTest.php) | 10 automated database constraint tests covering migrations, FKs, unique keys, and deletion integrity | Complete |
| [**Phase 5 Assessment Report**](database/phase-5-assessment.md) | Comprehensive Phase 5 and Sprint 1 database exit-gate assessment report | Complete |

---

## 7. Core Architectural Principles

1. **Two-Role Model:** Exactly two system roles exist: `SYSTEM_ADMIN` and `VEHICLE_OWNER`. Mechanics and service centers are descriptive metadata attributes on service transactions, **not** authenticated user roles.
2. **Strict Data Boundary:** Administrators oversee user accounts, global catalog maintenance types, audit logs, and system health. Administrators **do not** view private owner service histories without explicit user consent.
3. **Data Integrity Over Deletion:** Historical records and service invoices must be preserved. Vehicle deletion is an archival operation (`status = 'archived'`) to maintain financial and maintenance integrity.
4. **Zero-Dependency Native Architecture:** Native PHP 8+, PDO MySQL, and Bootstrap 5 without bloated third-party frameworks, maximizing maintainability, auditability, and ease of deployment.
