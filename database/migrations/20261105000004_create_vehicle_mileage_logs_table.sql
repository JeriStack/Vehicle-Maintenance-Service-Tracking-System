-- Migration: 20261105000004_create_vehicle_mileage_logs_table.sql
-- Description: Creates the vehicle mileage history audit log table.

CREATE TABLE IF NOT EXISTS `vehicle_mileage_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `mileage` INT NOT NULL,
    `source` VARCHAR(50) NOT NULL DEFAULT 'MANUAL',
    `recorded_by_user_id` INT NULL,
    `correction_reason` VARCHAR(255) NULL,
    `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vml_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vml_user` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_vml_vehicle_rec` (`vehicle_id`, `recorded_at`),
    INDEX `idx_vml_mileage` (`mileage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

