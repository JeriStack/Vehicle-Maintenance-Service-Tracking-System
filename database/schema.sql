-- database/schema.sql
-- Vehicle Maintenance Service Tracking System (VMS)
-- Clean Relational Database Schema Snapshot (Phase 5 Approved Baseline)

CREATE DATABASE IF NOT EXISTS vehicle_maintenance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vehicle_maintenance_db;

-- 1. Users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_normalized` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `auth_version` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_email_norm` (`email_normalized`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Maintenance Types (Global Catalog)
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

-- 3. Vehicles
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
    INDEX `idx_vehicles_user_status` (`user_id`, `status`),
    INDEX `idx_vehicles_reg_expiry` (`registration_expiry`),
    INDEX `idx_vehicles_status` (`status`),
    INDEX `idx_vehicles_make_model` (`make`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Vehicle Mileage Logs (Odometer Progression Audit)
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

-- 5. Service Records (Completed Transactions and Invoices)
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

-- 6. Maintenance Schedules (Dedicated Future Maintenance Targets)
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

-- 7. Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `vehicle_id` INT NULL,
    `schedule_id` INT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `dedupe_key` VARCHAR(191) NULL,
    `is_read` BOOLEAN NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notif_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notif_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `maintenance_schedules` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY `idx_notif_dedupe` (`dedupe_key`),
    INDEX `idx_notif_user_read` (`user_id`, `is_read`),
    INDEX `idx_notif_user_read_created` (`user_id`, `is_read`, `created_at`),
    INDEX `idx_notif_created_at` (`created_at`),
    INDEX `idx_notif_vehicle_id` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Notification Settings (1:1 with Users)
CREATE TABLE IF NOT EXISTS `notification_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `maintenance_reminder` BOOLEAN NOT NULL DEFAULT 1,
    `registration_reminder` BOOLEAN NOT NULL DEFAULT 1,
    `service_due_reminder` BOOLEAN NOT NULL DEFAULT 1,
    `overdue_reminder` BOOLEAN NOT NULL DEFAULT 1,
    `reminder_days` INT NOT NULL DEFAULT 7,
    `email_notifications` BOOLEAN NOT NULL DEFAULT 0,
    `push_notifications` BOOLEAN NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ns_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `idx_ns_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Vehicle Preferences (1:1 with Users)
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

-- 10. Audit Logs (Administrative & Security Event Trail)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `actor_type` VARCHAR(50) NOT NULL DEFAULT 'USER',
    `action` VARCHAR(255) NOT NULL,
    `table_name` VARCHAR(100) NULL,
    `record_id` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_audit_user_id` (`user_id`),
    INDEX `idx_audit_user_created` (`user_id`, `created_at`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_table` (`table_name`),
    INDEX `idx_audit_table_record` (`table_name`, `record_id`),
    INDEX `idx_audit_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Authentication Tokens
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `selector` VARCHAR(32) NOT NULL,
    `hashed_validator` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `idx_rt_selector` (`selector`),
    INDEX `idx_rt_user_id` (`user_id`),
    INDEX `idx_rt_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pr_email` (`email`),
    INDEX `idx_pr_token` (`token_hash`),
    INDEX `idx_pr_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Realtime Events Buffer
CREATE TABLE IF NOT EXISTS `realtime_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `channel` VARCHAR(100) NOT NULL,
    `event` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_re_processed` (`processed_at`),
    INDEX `idx_re_channel` (`channel`),
    INDEX `idx_re_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Login Throttling Attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `email_normalized` VARCHAR(255) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_la_ip_time` (`ip_address`, `attempted_at`),
    INDEX `idx_la_email_time` (`email_normalized`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. System Settings & Bootstrap Metadata
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

