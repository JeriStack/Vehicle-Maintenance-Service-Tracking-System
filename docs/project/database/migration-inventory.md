# Migration Inventory Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-001  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 2 ERD, Data Dictionary, and Phase 4 Application Foundation  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Overview

This document maintains the authoritative inventory of all ordered, version-controlled database migrations registered in the Vehicle Maintenance Service Tracking System. All migrations follow a strict chronological timestamp prefix (`YYYYMMDDHHMMSS_{description}.sql`) and are executed sequentially by `VMS\Core\MigrationRunner` via `php bin/console migrate`.

---

## 2. Migration Registry & Dependency Map

| Seq | Migration Identifier | Affected Tables | Purpose / Architectural Description | Prerequisites | Rollback Support |
|---|---|---|---|---|---|
| **01** | `20261105000001_create_users_table.sql` | `users` | Creates authenticated user accounts with normalized email uniqueness, role constraint (`user`/`admin`), and lifecycle status (`active`/`inactive`). | None | Forward-fix / Drop table |
| **02** | `20261105000002_create_maintenance_types_table.sql` | `maintenance_types` | Creates the global maintenance catalog with stable immutable codes (`oil-change`, `tire-rotation`, etc.) and slugs. | None | Forward-fix / Drop table |
| **03** | `20261105000003_create_vehicles_table.sql` | `vehicles` | Creates owner-scoped vehicle garage with composite unique normalized plate constraint (`user_id, plate_number_normalized`). | `users` | Forward-fix / Drop table |
| **04** | `20261105000004_create_vehicle_mileage_logs_table.sql` | `vehicle_mileage_logs` | Creates historical odometer progression audit trail with actor user attribution and vehicle cascade. | `vehicles`, `users` | Forward-fix / Drop table |
| **05** | `20261105000005_create_service_records_table.sql` | `service_records` | Creates completed maintenance transactions, decimal financial costs (`DECIMAL(10,2)`), void auditing, and maintenance type references. | `vehicles`, `maintenance_types` | Forward-fix / Drop table |
| **06** | `20261105000006_create_maintenance_schedules_table.sql` | `maintenance_schedules` | Creates dedicated future maintenance targets separating projections from historical transactions. | `vehicles`, `maintenance_types`, `service_records` | Forward-fix / Drop table |
| **07** | `20261105000007_create_notifications_table.sql` | `notifications` | Creates user in-app notifications with deterministic SHA-256 deduplication key (`dedupe_key UNIQUE`). | `users`, `vehicles`, `maintenance_schedules` | Forward-fix / Drop table |
| **08** | `20261105000008_create_notification_settings_table.sql` | `notification_settings` | Creates 1:1 user reminder preferences, lead days window, and email/push channel toggles. | `users` | Forward-fix / Drop table |
| **09** | `20261105000009_create_vehicle_preferences_table.sql` | `vehicle_preferences` | Creates 1:1 user vehicle default servicing intervals (months and km). | `users` | Forward-fix / Drop table |
| **10** | `20261105000010_create_audit_logs_table.sql` | `audit_logs` | Creates administrative and security audit trail with `SET NULL` actor preservation and system daemon support. | `users` | Forward-fix / Drop table |
| **11** | `20261105000011_create_auth_tokens_tables.sql` | `remember_tokens`, `password_resets` | Creates cryptographic split-token remember-me persistence and password reset request tokens. | `users` | Forward-fix / Drop table |
| **12** | `20261105000012_create_realtime_events_table.sql` | `realtime_events` | Creates persistent WebSocket broadcast buffer for real-time notification delivery. | None | Forward-fix / Drop table |
| **13** | `20261105000013_reconcile_existing_schema.sql` | `users`, `vehicles`, `service_records`, `notifications`, `audit_logs` | Reconciles pre-existing legacy database structures, backfills normalized fields, and applies missing constraints safely. | Migrations 01–12 | Forward-fix idempotent |

---

## 3. Migration Tracking Mechanism

Migrations are tracked in the database inside the `migrations` table:

```sql
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL UNIQUE,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- Each migration run assigns an incremental `batch` number (`MAX(batch) + 1`).
- Already applied migrations are skipped automatically.
- Migration status is inspectable via `php bin/console migrate:status`.
