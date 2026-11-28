-- Migration: 20261128000015_optimize_composite_indexes.sql
-- Description: Adds composite indexes for owner garage, unread notifications, and audit log queries,
--              and drops redundant duplicate index idx_sys_key on system_settings if present.
-- Compatibility: MySQL 8.0+ and MariaDB 10.4+

DROP PROCEDURE IF EXISTS _vms_optimize_indexes;
CREATE PROCEDURE _vms_optimize_indexes()
BEGIN
    -- 1. vehicles(user_id, status) for owner-scoped active/archived garage listings
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vehicles' AND INDEX_NAME = 'idx_vehicles_user_status'
    ) THEN
        ALTER TABLE `vehicles` ADD INDEX `idx_vehicles_user_status` (`user_id`, `status`);
    END IF;

    -- 2. notifications(user_id, is_read, created_at) for ordered unread notification dropdowns
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notif_user_read_created'
    ) THEN
        ALTER TABLE `notifications` ADD INDEX `idx_notif_user_read_created` (`user_id`, `is_read`, `created_at`);
    END IF;

    -- 3. audit_logs(table_name, record_id) and audit_logs(user_id, created_at) for admin log filtering
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_table_record'
    ) THEN
        ALTER TABLE `audit_logs` ADD INDEX `idx_audit_table_record` (`table_name`, `record_id`);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_user_created'
    ) THEN
        ALTER TABLE `audit_logs` ADD INDEX `idx_audit_user_created` (`user_id`, `created_at`);
    END IF;

    -- 4. Drop redundant non-unique idx_sys_key on system_settings (setting_key is already UNIQUE)
    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_settings' AND INDEX_NAME = 'idx_sys_key'
    ) THEN
        ALTER TABLE `system_settings` DROP INDEX `idx_sys_key`;
    END IF;
END;
CALL _vms_optimize_indexes();
DROP PROCEDURE IF EXISTS _vms_optimize_indexes;
