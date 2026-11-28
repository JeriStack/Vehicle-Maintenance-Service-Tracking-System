-- Migration: 20261105000002_create_maintenance_types_table.sql
-- Description: Creates the global standardized maintenance catalog table with stable identifiers.

CREATE TABLE IF NOT EXISTS `maintenance_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `default_interval_days` INT NULL,
    `default_interval_distance_km` INT NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_mt_code` (`code`),
    UNIQUE KEY `idx_mt_slug` (`slug`),
    INDEX `idx_mt_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

