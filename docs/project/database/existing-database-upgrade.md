# Existing Database Upgrade Guide (Scenario B)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-010  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 4 & Phase 5 Foundation Specifications  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Scenario Context

Scenario B covers upgrading an existing VMS project database that predates formal migration tracking (e.g. databases created from legacy `schema.sql`). The upgrade process preserves 100% of existing user accounts, registered vehicles, service transactions, and settings while applying newly required columns, constraints, and indexes.

---

## 2. Upgrade Procedure

### Step 1: Pre-Upgrade Database Backup
**Mandatory Safety Gate:** Never apply migrations to existing application data without an independent verified backup:
```bash
# On Windows / XAMPP:
& "c:\xampp\mysql\bin\mysqldump.exe" -u root vehicle_maintenance_db | Out-File -Encoding utf8 "storage/backups/pre_upgrade_backup.sql"

# On Linux / macOS:
mysqldump -u root -p vehicle_maintenance_db > storage/backups/pre_upgrade_backup.sql
```

### Step 2: Inspect Existing Data Integrity
Verify existing row counts to establish baseline metrics:
- Users count
- Vehicles count
- Service records count
- Notification settings count

### Step 3: Run Incremental Migrations
Execute `bin/console migrate`:
```bash
php bin/console migrate
```
The migration sequence operates idempotently:
1. Migrations 01 through 12 use `CREATE TABLE IF NOT EXISTS`, preserving existing tables and creating missing infrastructure (`maintenance_types`, `vehicle_mileage_logs`, `maintenance_schedules`, `remember_tokens`, `password_resets`, `realtime_events`).
2. Migration 13 (`20261105000013_reconcile_existing_schema.sql`) executes safe inspection procedures to:
   - Backfill `email_normalized` for existing users (`LOWER(TRIM(email))`).
   - Backfill `plate_number_normalized` for existing vehicles (`UPPER(REPLACE(REPLACE(plate_number, ' ', ''), '-', ''))`).
   - Add owner-scoped unique key `(user_id, plate_number_normalized)`.
   - Add `maintenance_type_id` and `void_reason` to `service_records`.
   - Add `dedupe_key` and `read_at` to `notifications`.
   - Add `actor_type` to `audit_logs`.

### Step 4: Seed Global Catalog
Run `php bin/console seed` to populate missing catalog items:
```bash
php bin/console seed
```
The `ON DUPLICATE KEY UPDATE` design ensures existing records are untouched while missing reference categories are added.

### Step 5: Post-Upgrade Verification
Verify that all existing records are intact:
```sql
SELECT id, email, email_normalized, status FROM users;
SELECT id, plate_number, plate_number_normalized FROM vehicles;
SELECT id, service_type, cost, status FROM service_records;
```
Confirm that all 13 migrations are registered in `migrations`:
```bash
php bin/console migrate:status
```
