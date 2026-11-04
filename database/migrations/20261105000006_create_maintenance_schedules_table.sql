-- Migration: 20261105000006_create_maintenance_schedules_table.sql
-- Description: Creates the future maintenance schedules table separating targets from completed work.

CREATE TABLE IF NOT EXISTS `maintenance_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `maintenance_type_id` INT NULL,
    `source_service_id` INT NULL,
    `title` VARCHAR(120) NOT NULL,
    `next_service_date` DATE NULL,
    `next_service_mileage` INT NULL,
    `interval_days` INT NULL,
    `interval_distance_km` INT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
    `notes` TEXT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ms_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ms_mt` FOREIGN KEY (`maintenance_type_id`) REFERENCES `maintenance_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ms_source_srv` FOREIGN KEY (`source_service_id`) REFERENCES `service_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_ms_vehicle_status` (`vehicle_id`, `status`),
    INDEX `idx_ms_next_date` (`next_service_date`),
    INDEX `idx_ms_next_km` (`next_service_mileage`),
    INDEX `idx_ms_mt_id` (`maintenance_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
