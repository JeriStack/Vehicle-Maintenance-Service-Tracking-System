# Database Migration Standards & Schema Versioning
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-MIG-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, ADR-004 (Database Migration Strategy)  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. Core Principles & Zero Data-Loss Guarantee

1. **Non-Destructive Alterations:** Schema changes must never delete, drop, or truncate existing columns or tables containing production user data without an approved multi-step data transition plan.
2. **Strict Idempotency:** Every migration script must be idempotent—executing the script multiple times on the same database must yield the exact same end state without errors (`IF NOT EXISTS` syntax).
3. **Phase-Specific Safety Boundary:** In accordance with Phase 3 execution constraints, **no database migrations may be executed during Phase 3**. Migration execution begins in Phase 4 / Sprint 1.

---

## 2. Migration File Naming Convention

Migration files are stored in `database/migrations/` and must conform strictly to the timestamp-prefixed snake-case standard:

```text
database/migrations/
└── YYYYMMDDHHMMSS_{description}.sql
```

*Concrete Examples:*
- `20261105000001_create_maintenance_types_table.sql`
- `20261105000002_create_maintenance_schedules_table.sql`
- `20261105000003_create_vehicle_mileage_logs_table.sql`
- `20261105000004_add_vehicle_owner_indexes.sql`

---

## 3. SQL Syntax & Idempotency Rules

### 3.1 Creating New Tables
```sql
-- Migration: 20261105000001_create_maintenance_types_table.sql
CREATE TABLE IF NOT EXISTS `maintenance_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `default_months` INT UNSIGNED NULL,
    `default_mileage` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_maintenance_types_name` (`name`),
    INDEX `idx_maintenance_types_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Modifying Existing Tables (Adding Columns Idempotently)
For MariaDB 10.4+, column additions must use `IF NOT EXISTS` syntax:
```sql
ALTER TABLE `vehicles` 
ADD COLUMN IF NOT EXISTS `registration_expiry_date` DATE NULL AFTER `current_mileage`,
ADD COLUMN IF NOT EXISTS `archived_at` DATETIME NULL AFTER `status`;
```

---

## 4. Rollback & Disaster Recovery Procedures

1. **Rollback Script Pairing:** For every forward migration file `YYYYMMDDHHMMSS_{desc}.sql`, a corresponding rollback file `YYYYMMDDHHMMSS_{desc}_rollback.sql` must be drafted and verified in the test database.
2. **Pre-Migration Snapshot:** Before executing any migration in staging or production, a full logical database backup must be created:
   ```bash
   mysqldump -u root -p vms_tracker > database/backups/pre_migration_backup_$(date +%Y%m%d%H%M%S).sql
   ```
3. **Emergency Rollback Execution:** If a migration fails midway, the transaction aborts, and the system restores from the pre-migration snapshot.

