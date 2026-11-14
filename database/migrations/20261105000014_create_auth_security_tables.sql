-- Migration: 20261105000014_create_auth_security_tables.sql
-- Description: Adds auth_version column to users for session invalidation,
--              creates login_attempts table for persistent login throttling,
--              and creates system_settings table for one-time CLI bootstrap marker.

-- 1. Add auth_version to users table if not exists
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `auth_version` INT NOT NULL DEFAULT 1 AFTER `status`;

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
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sys_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
