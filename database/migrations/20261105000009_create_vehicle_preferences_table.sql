-- Migration: 20261105000009_create_vehicle_preferences_table.sql
-- Description: Creates per-user vehicle servicing preferences table with 1:1 user constraint.

CREATE TABLE IF NOT EXISTS `vehicle_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `default_service_interval` INT NOT NULL DEFAULT 6,
    `default_mileage_interval` INT NOT NULL DEFAULT 5000,
    `preferred_service_provider` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `idx_vp_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

