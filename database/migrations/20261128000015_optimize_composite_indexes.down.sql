-- Reversible down migration for 20261128000015_optimize_composite_indexes.sql
-- Compatibility: MySQL 8.0+ and MariaDB 10.4+

DROP PROCEDURE IF EXISTS _vms_revert_optimize_indexes;
CREATE PROCEDURE _vms_revert_optimize_indexes()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vehicles' AND INDEX_NAME = 'idx_vehicles_user_status'
    ) THEN
        ALTER TABLE `vehicles` DROP INDEX `idx_vehicles_user_status`;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND INDEX_NAME = 'idx_notif_user_read_created'
    ) THEN
        ALTER TABLE `notifications` DROP INDEX `idx_notif_user_read_created`;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_table_record'
    ) THEN
        ALTER TABLE `audit_logs` DROP INDEX `idx_audit_table_record`;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND INDEX_NAME = 'idx_audit_user_created'
    ) THEN
        ALTER TABLE `audit_logs` DROP INDEX `idx_audit_user_created`;
    END IF;
END;
CALL _vms_revert_optimize_indexes();
DROP PROCEDURE IF EXISTS _vms_revert_optimize_indexes;
