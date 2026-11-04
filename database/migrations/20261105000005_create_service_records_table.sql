-- Migration: 20261105000005_create_service_records_table.sql
-- Description: Creates the transactional completed service records and invoices table.

CREATE TABLE IF NOT EXISTS `service_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `maintenance_type_id` INT NULL,
    `service_type` VARCHAR(100) NOT NULL,
    `service_date` DATE NOT NULL,
    `mileage` INT NOT NULL,
    `description` TEXT NULL,
    `parts_replaced` TEXT NULL,
    `service_provider` VARCHAR(255) NULL,
    `cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `next_service_date` DATE NULL,
    `next_service_mileage` INT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Completed',
    `void_reason` VARCHAR(255) NULL,
    `remarks` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_sr_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sr_mt` FOREIGN KEY (`maintenance_type_id`) REFERENCES `maintenance_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_sr_vehicle_id` (`vehicle_id`),
    INDEX `idx_sr_service_date` (`service_date`),
    INDEX `idx_sr_status` (`status`),
    INDEX `idx_sr_mt_id` (`maintenance_type_id`),
    INDEX `idx_sr_next_date` (`next_service_date`),
    INDEX `idx_sr_composite_dash` (`vehicle_id`, `status`, `service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
