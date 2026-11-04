-- Migration: 20261105000013_reconcile_existing_schema.sql
-- Description: Reconciles pre-existing database installations with Phase 2 specifications safely and idempotently.

DROP PROCEDURE IF EXISTS _vms_reconcile_schema;
CREATE PROCEDURE _vms_reconcile_schema()
BEGIN
    -- 1. Reconcile users
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_normalized'
    ) THEN
        ALTER TABLE `users` ADD COLUMN `email_normalized` VARCHAR(255) NULL AFTER `email`;
        UPDATE `users` SET `email_normalized` = LOWER(TRIM(`email`)) WHERE `email_normalized` IS NULL;
        ALTER TABLE `users` MODIFY COLUMN `email_normalized` VARCHAR(255) NOT NULL;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_email_norm'
        ) THEN
            ALTER TABLE `users` ADD UNIQUE KEY `idx_users_email_norm` (`email_normalized`);
        END IF;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'
    ) THEN
        ALTER TABLE `users` ADD COLUMN `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER `role`;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_status'
        ) THEN
            ALTER TABLE `users` ADD INDEX `idx_users_status` (`status`);
        END IF;
    END IF;

    -- 2. Reconcile vehicles
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vehicles' AND COLUMN_NAME = 'plate_number_normalized'
    ) THEN
        ALTER TABLE `vehicles` ADD COLUMN `plate_number_normalized` VARCHAR(50) NULL AFTER `plate_number`;
        UPDATE `vehicles` SET `plate_number_normalized` = UPPER(REPLACE(REPLACE(`plate_number`, ' ', ''), '-', '')) WHERE `plate_number_normalized` IS NULL;
        ALTER TABLE `vehicles` MODIFY COLUMN `plate_number_normalized` VARCHAR(50) NOT NULL;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vehicles' AND INDEX_NAME = 'idx_vehicles_owner_plate_norm'
        ) THEN
            ALTER TABLE `vehicles` ADD UNIQUE KEY `idx_vehicles_owner_plate_norm` (`user_id`, `plate_number_normalized`);
        END IF;
    END IF;

    -- 3. Reconcile service_records
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_records' AND COLUMN_NAME = 'maintenance_type_id'
    ) THEN
        ALTER TABLE `service_records` ADD COLUMN `maintenance_type_id` INT NULL AFTER `vehicle_id`;
        ALTER TABLE `service_records` ADD CONSTRAINT `fk_sr_mt` FOREIGN KEY (`maintenance_type_id`) REFERENCES `maintenance_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_records' AND INDEX_NAME = 'idx_sr_mt_id'
        ) THEN
            ALTER TABLE `service_records` ADD INDEX `idx_sr_mt_id` (`maintenance_type_id`);
        END IF;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'service_records' AND COLUMN_NAME = 'void_reason'
    ) THEN
        ALTER TABLE `service_records` ADD COLUMN `void_reason` VARCHAR(255) NULL AFTER `status`;
    END IF;

    -- 4. Reconcile notifications
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'dedupe_key'
    ) THEN
        ALTER TABLE `notifications` ADD COLUMN `dedupe_key` VARCHAR(191) NULL AFTER `message`;
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notif_dedupe'
        ) THEN
            ALTER TABLE `notifications` ADD UNIQUE KEY `idx_notif_dedupe` (`dedupe_key`);
        END IF;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'read_at'
    ) THEN
        ALTER TABLE `notifications` ADD COLUMN `read_at` DATETIME NULL AFTER `is_read`;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'schedule_id'
    ) THEN
        ALTER TABLE `notifications` ADD COLUMN `schedule_id` INT NULL AFTER `vehicle_id`;
        ALTER TABLE `notifications` ADD CONSTRAINT `fk_notif_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;

    -- 5. Reconcile audit_logs
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'actor_type'
    ) THEN
        ALTER TABLE `audit_logs` ADD COLUMN `actor_type` VARCHAR(50) NOT NULL DEFAULT 'USER' AFTER `user_id`;
    END IF;
END;
CALL _vms_reconcile_schema();
DROP PROCEDURE IF EXISTS _vms_reconcile_schema;
