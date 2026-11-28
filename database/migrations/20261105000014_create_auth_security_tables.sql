-- Migration: 20261105000014_create_auth_security_tables.sql
-- Description: Adds auth_version column to users for session invalidation,
--              creates login_attempts table for persistent login throttling,
--              and creates system_settings table for one-time CLI bootstrap marker.
-- Compatibility: MySQL 8.0+ and MariaDB 10.4+

-- 1. Add auth_version to users table portably (MySQL 8.0 + MariaDB 10.4)
DROP PROCEDURE IF EXISTS _vms_add_auth_version;
CREATE PROCEDURE _vms_add_auth_version()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'auth_version'
    ) THEN
        ALTER TABLE `users` ADD COLUMN `auth_version` INT NOT NULL DEFAULT 1 AFTER `status`;
    END IF;
END;
CALL _vms_add_auth_version();
DROP PROCEDURE IF EXISTS _vms_add_auth_version;

-- 2. Create login_attempts table for rate limiting / brute force throttling
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `email_normalized` VARCHAR(255) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_la_ip_time` (`ip_address`, `attempted_at`),
    INDEX `idx_la_email_time` (`email_normalized`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create system_settings table for durable system metadata and bootstrap markers
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
