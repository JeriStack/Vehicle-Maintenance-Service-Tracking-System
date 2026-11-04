# Phase 5 — Database Schema, Migrations, and Seed Data Assessment & Sprint 1 Exit Report

**Project:** Vehicle Maintenance Service Tracking System (VMS)  
**Repository:** `Vehicle-Maintenance-Service-Tracking-System`  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Sprint Mapping:** Sprint 1 — Foundation  
**Status:** **COMPLETED**  
**Date:** November 2026  
**Document Classification:** Database Engineering Assessment & Schema Baseline  

---

## 1. Executive Summary

Phase 5 establishes the enterprise-grade relational database architecture for the Vehicle Maintenance Service Tracking System. Built on MySQL / MariaDB using the `InnoDB` storage engine and `utf8mb4_unicode_ci` character encoding, this phase transitions the database from unstructured legacy SQL files to a deterministic, ordered, and version-controlled migration suite (13 migrations).

All ten approved core domain entities—plus authentication tokens and real-time event tables—have been implemented and verified against both a fresh installation (Scenario A) and the existing project database (Scenario B). The implementation guarantees two-role account governance (`user`/`admin`), normalized case-insensitive email uniqueness, owner-scoped normalized license plate uniqueness (`user_id, plate_number_normalized`), tamper-proof odometer progression auditing, decimal financial tracking (`DECIMAL(10,2)`), dedicated future maintenance scheduling, deterministic notification deduplication (`SHA-256 dedupe_key`), and immutable security audit trails.

With all 10 dedicated database integration tests, 12 smoke tests, and 44 unit tests passing (66 total tests, 238 assertions, 0 failures), Phase 5 is fully verified and complete.

---

## 2. Existing Database Assessment

Prior to executing migrations, a comprehensive preflight inspection of the local XAMPP environment was conducted:
- **Database Server:** MariaDB `10.4.32-MariaDB` (running on Windows NT, port 3306).
- **Existing Database Name:** `vehicle_maintenance_db`.
- **Pre-Migration Table Count:** 9 tables (`audit_logs`, `maintenance_reminders`, `migrations`, `notification_settings`, `notifications`, `service_records`, `users`, `vehicle_preferences`, `vehicles`).
- **Pre-Migration Data Audit:**
  - `users`: 2 existing accounts (`demo@example.com` [user], `admin@example.com` [admin]).
  - `vehicles`: 3 registered vehicles (`ABC-1234`, `XYZ-5678`, `DEF-9012`).
  - `service_records`: 3 historical invoices ($150.00, $80.00, $250.00).
  - `notifications`: 3 alert records.
  - `audit_logs`: 9 administrative logs.
- **Data Protection Gate:** Full database backup generated prior to migration execution:
  - Backup File: `storage/backups/vehicle_maintenance_db_pre_phase5_backup_20261105.sql` (14,853 bytes).
  - Verification: Complete table structures and existing row data confirmed.

---

## 3. Migration Implementation & Inventory

All un-versioned legacy patch scripts were superseded by 13 ordered, version-controlled migrations:

| Order | Migration Filename | Primary Entity / Target | Operation Type | Status |
|---|---|---|---|---|
| **01** | `20261105000001_create_users_table.sql` | `users` | Table Creation / Identity | **Applied** (Batch 1) |
| **02** | `20261105000002_create_maintenance_types_table.sql` | `maintenance_types` | Table Creation / Global Catalog | **Applied** (Batch 1) |
| **03** | `20261105000003_create_vehicles_table.sql` | `vehicles` | Table Creation / Owner Garage | **Applied** (Batch 1) |
| **04** | `20261105000004_create_vehicle_mileage_logs_table.sql` | `vehicle_mileage_logs` | Table Creation / Odometer Audit | **Applied** (Batch 1) |
| **05** | `20261105000005_create_service_records_table.sql` | `service_records` | Table Creation / Invoices | **Applied** (Batch 1) |
| **06** | `20261105000006_create_maintenance_schedules_table.sql` | `maintenance_schedules` | Table Creation / Projections | **Applied** (Batch 1) |
| **07** | `20261105000007_create_notifications_table.sql` | `notifications` | Table Creation / Alert Inbox | **Applied** (Batch 1) |
| **08** | `20261105000008_create_notification_settings_table.sql` | `notification_settings` | Table Creation / 1:1 Preferences | **Applied** (Batch 1) |
| **09** | `20261105000009_create_vehicle_preferences_table.sql` | `vehicle_preferences` | Table Creation / 1:1 Servicing | **Applied** (Batch 1) |
| **10** | `20261105000010_create_audit_logs_table.sql` | `audit_logs` | Table Creation / Security Audit | **Applied** (Batch 1) |
| **11** | `20261105000011_create_auth_tokens_tables.sql` | `remember_tokens`, `password_resets` | Table Creation / Auth Tokens | **Applied** (Batch 1) |
| **12** | `20261105000012_create_realtime_events_table.sql` | `realtime_events` | Table Creation / Realtime Buffer | **Applied** (Batch 1) |
| **13** | `20261105000013_reconcile_existing_schema.sql` | `users`, `vehicles`, `service_records`, `notifications`, `audit_logs` | Schema Reconciliation & Backfill | **Applied** (Batch 1) |

---

## 4. Referential Integrity & Constraints Verification

### 4.1 Primary & Foreign Keys
- All 13 tables feature integer auto-increment primary keys (`PRIMARY KEY (id)`).
- All 15 relational foreign keys are strictly enforced using `InnoDB` with explicit `ON DELETE` and `ON UPDATE` actions.
- `audit_logs.user_id` uses `ON DELETE SET NULL`, preserving historical audit events even if the actor account is purged.
- `service_records.maintenance_type_id` uses `ON DELETE SET NULL`, guaranteeing invoice records are never corrupted if a catalog maintenance item is deactivated.

### 4.2 Unique Constraints & Normalization
- **Normalized Email (`idx_users_email_norm`):** Enforces lowercase normalized uniqueness. Duplicate variations (e.g. `DEMO@example.com` vs `demo@example.com`) are permanently rejected by the database engine.
- **Owner-Scoped Plate Uniqueness (`idx_vehicles_owner_plate_norm`):** Composite unique constraint on `(user_id, plate_number_normalized)`. Prevents the same owner from registering duplicate plates while allowing distinct owners to register identical normalized plate values (`BR-VEH-02`).
- **Notification Deduplication (`idx_notif_dedupe`):** Unique constraint on `dedupe_key` (SHA-256 hash). Prevents automated scheduler runs from generating duplicate alerts.
- **1:1 Relationships:** Unique constraints on `notification_settings.user_id` and `vehicle_preferences.user_id` prevent duplicate profile rows.

### 4.3 Data Types & Decimal Precision
- All monetary fields (`service_records.cost`) utilize `DECIMAL(10,2)` with a default of `0.00`. Floating-point currency types (`FLOAT`, `DOUBLE`) are strictly disallowed.
- All mileage fields (`current_mileage`, `mileage`, `next_service_mileage`) utilize integers with non-negative constraints.

---

## 5. Reference Catalog Seeder

The global maintenance catalog is seeded idempotently via `Database\Seeders\MaintenanceTypeSeeder`:
- **Stable Identifiers:** Eight immutable catalog codes (`oil-change`, `tire-rotation`, `brake-check`, `battery-replacement`, `engine-service`, `transmission-service`, `vehicle-inspection`, `other`).
- **Idempotent Execution:** Seeded via `INSERT ... ON DUPLICATE KEY UPDATE` keyed on `code`. Re-running `php bin/console seed` preserves existing record IDs and transaction history.
- **Zero Dummy Data:** Production seeders contain zero sample user accounts, fake credentials, or dummy vehicles.

---

## 6. Testing & Automated Verification Results

### 6.1 Database Integration Suite (`tests/Integration/Database/DatabaseIntegrationTest.php`)
A dedicated integration suite was executed against an isolated, disposable database (`vms_disposable_test_db`):

| Test Case | Description | Result |
|---|---|---|
| `testFreshDatabaseMigrationsAndTableEngines` | Executes all 13 migrations on empty database; verifies InnoDB engine across all tables | **PASS** |
| `testUserConstraintsAndNormalizedEmail` | Verifies user creation, email normalization, and duplicate rejection | **PASS** |
| `testVehicleOwnerScopedPlateUniqueness` | Verifies owner duplicate rejection and cross-owner plate acceptance | **PASS** |
| `testVehicleMileageHistoryLogging` | Verifies non-negative odometer progression audit trail | **PASS** |
| `testServiceRecordsAndDecimalPrecision` | Verifies transactional invoices and `DECIMAL(10,2)` precision | **PASS** |
| `testMaintenanceSchedulesTriggers` | Verifies date-only, mileage-only, and combined schedule triggers | **PASS** |
| `testNotificationDeduplicationConstraint` | Verifies SHA-256 dedupe key uniqueness | **PASS** |
| `testOneToOneSettingsAndPreferences` | Verifies 1:1 constraints on settings and preferences tables | **PASS** |
| `testAuditLogActorPreservation` | Verifies CLI daemon logging and SET NULL user deletion behavior | **PASS** |
| `testForeignKeyDeletionIntegrity` | Verifies vehicle soft-archival preserves service and mileage history | **PASS** |

### 6.2 Full Test Suite Execution
```text
====================================================================
 Vehicle Maintenance Service Tracking System - Complete Test Suite
 PHP Version: 8.5.12 (WINNT)
 Target Files: 13 test files discovered
====================================================================
Total Tests:      66 (44 Unit + 12 Smoke + 10 Database Integration)
Total Assertions: 238
Passed:           66
Failed:           0
Time Elapsed:     345.04 ms
Peak Memory:      4 MB
====================================================================
SUCCESS! All unit, smoke, and database tests passed without any errors.
```

---

## 7. Documentation Deliverables Index

All documentation deliverables have been created and placed in [`docs/project/database/`](./):
1. [`migration-inventory.md`](migration-inventory.md): Full registry of all 13 migrations and dependency tree.
2. [`schema-implementation.md`](schema-implementation.md): Complete physical column definitions, data types, and storage engines.
3. [`erd-verification.md`](erd-verification.md): Mermaid ERD and relational constraint verification matrix.
4. [`foreign-key-catalogue.md`](foreign-key-catalogue.md): Referential integrity matrix, delete actions, and archival policies.
5. [`index-catalogue.md`](index-catalogue.md): Complete index definitions and supported query workloads.
6. [`normalization-and-constraints.md`](normalization-and-constraints.md): Business normalization rules and check constraints.
7. [`seed-data.md`](seed-data.md): Reference maintenance catalog entries and idempotency rules.
8. [`rollback-and-forward-fix-policy.md`](rollback-and-forward-fix-policy.md): Reversible migration and forward-fix recovery protocols.
9. [`clean-installation-guide.md`](clean-installation-guide.md): Step-by-step instructions for Scenario A (clean install).
10. [`existing-database-upgrade.md`](existing-database-upgrade.md): Step-by-step instructions for Scenario B (existing upgrade).
11. [`phase-5-assessment.md`](phase-5-assessment.md): This comprehensive assessment report.

---

## 8. Phase 5 Exit-Gate Determination

### Assessment Gate Criteria:
- [x] All 10 approved core domain entities + auth & realtime tables implemented.
- [x] Storage engine strictly `InnoDB` with `utf8mb4_unicode_ci` character encoding.
- [x] All foreign key relationships and deletion actions enforced.
- [x] Normalized email and owner-scoped plate uniqueness enforced.
- [x] Dedicated future maintenance schedules table decoupled from historical records.
- [x] Monetary data uses `DECIMAL(10,2)` with zero float/double representations.
- [x] All 13 ordered migrations pass on an empty database (Scenario A).
- [x] Existing database (`vehicle_maintenance_db`) upgraded with 100% data preservation (Scenario B).
- [x] Maintenance catalog seeded idempotently with stable codes (8 items).
- [x] 10/10 database integration tests pass (66/66 total suite tests pass).
- [x] Complete documentation deliverables produced in `docs/project/database/`.

### Final Status:
**COMPLETED.** The database schema, migrations, referential constraints, and seed data for Phase 5 are fully implemented, verified, and complete. Sprint 1 (Foundation) has achieved all database exit gates.
