-- Migration: 20261105000003_create_vehicles_table.sql
-- Description: Creates the owner-scoped vehicles table with normalized plate uniqueness per user.

CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `plate_number` VARCHAR(50) NOT NULL,
    `plate_number_normalized` VARCHAR(50) NOT NULL,
    `vehicle_type` VARCHAR(100) NOT NULL,
    `make` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `year` INT NOT NULL,
    `color` VARCHAR(50) NULL,
    `vin_number` VARCHAR(100) NULL,
    `current_mileage` INT NOT NULL DEFAULT 0,
    `registration_date` DATE NULL,
    `registration_expiry` DATE NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `idx_vehicles_owner_plate_norm` (`user_id`, `plate_number_normalized`),
    INDEX `idx_vehicles_user_id` (`user_id`),
    INDEX `idx_vehicles_reg_expiry` (`registration_expiry`),
    INDEX `idx_vehicles_status` (`status`),
    INDEX `idx_vehicles_make_model` (`make`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
