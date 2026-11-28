-- Reversible down migration for 20261105000014_create_auth_security_tables.sql
-- Compatibility: MySQL 8.0+ and MariaDB 10.4+

DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `system_settings`;

DROP PROCEDURE IF EXISTS _vms_drop_auth_version;
CREATE PROCEDURE _vms_drop_auth_version()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'auth_version'
    ) THEN
        ALTER TABLE `users` DROP COLUMN `auth_version`;
    END IF;
END;
CALL _vms_drop_auth_version();
DROP PROCEDURE IF EXISTS _vms_drop_auth_version;
