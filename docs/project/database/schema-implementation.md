# Physical Schema Implementation Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-002  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 2 ERD & Data Dictionary  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Engine & Character Encoding Standards

All tables in the VMS relational architecture adhere strictly to enterprise persistence standards:
- **Storage Engine:** `InnoDB` across all 13 physical tables, ensuring full ACID transactional compliance, row-level locking, and foreign key enforcement.
- **Character Set:** `utf8mb4` supporting full multi-byte Unicode (including emoji, international characters, and standard alphanumeric text).
- **Collation:** `utf8mb4_unicode_ci` providing accent-insensitive, case-insensitive comparison for text display while precise canonical comparisons are enforced via normalized unique indexes.

---

## 2. Table-by-Table Physical Schema Summary

### 2.1 Table: `users`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `full_name` VARCHAR(255) NOT NULL
- `email` VARCHAR(255) NOT NULL UNIQUE
- `email_normalized` VARCHAR(255) NOT NULL UNIQUE
- `password` VARCHAR(255) NOT NULL
- `phone` VARCHAR(50) NULL
- `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user'
- `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.2 Table: `maintenance_types`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `code` VARCHAR(50) NOT NULL UNIQUE
- `name` VARCHAR(100) NOT NULL
- `slug` VARCHAR(100) NOT NULL UNIQUE
- `description` TEXT NULL
- `default_interval_days` INT NULL
- `default_interval_distance_km` INT NULL
- `is_active` BOOLEAN NOT NULL DEFAULT 1
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.3 Table: `vehicles`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL (FK -> `users.id` ON DELETE CASCADE)
- `plate_number` VARCHAR(50) NOT NULL
- `plate_number_normalized` VARCHAR(50) NOT NULL
- `vehicle_type` VARCHAR(100) NOT NULL
- `make` VARCHAR(100) NOT NULL
- `model` VARCHAR(100) NOT NULL
- `year` INT NOT NULL
- `color` VARCHAR(50) NULL
- `vin_number` VARCHAR(100) NULL
- `current_mileage` INT NOT NULL DEFAULT 0
- `registration_date` DATE NULL
- `registration_expiry` DATE NOT NULL
- `status` VARCHAR(50) NOT NULL DEFAULT 'Active'
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.4 Table: `vehicle_mileage_logs`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `vehicle_id` INT NOT NULL (FK -> `vehicles.id` ON DELETE CASCADE)
- `mileage` INT NOT NULL
- `source` VARCHAR(50) NOT NULL DEFAULT 'MANUAL'
- `recorded_by_user_id` INT NULL (FK -> `users.id` ON DELETE SET NULL)
- `correction_reason` VARCHAR(255) NULL
- `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP

### 2.5 Table: `service_records`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `vehicle_id` INT NOT NULL (FK -> `vehicles.id` ON DELETE CASCADE)
- `maintenance_type_id` INT NULL (FK -> `maintenance_types.id` ON DELETE SET NULL)
- `service_type` VARCHAR(100) NOT NULL
- `service_date` DATE NOT NULL
- `mileage` INT NOT NULL
- `description` TEXT NULL
- `parts_replaced` TEXT NULL
- `service_provider` VARCHAR(255) NULL
- `cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00
- `next_service_date` DATE NULL
- `next_service_mileage` INT NULL
- `status` VARCHAR(50) NOT NULL DEFAULT 'Completed'
- `void_reason` VARCHAR(255) NULL
- `remarks` TEXT NULL
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.6 Table: `maintenance_schedules`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `vehicle_id` INT NOT NULL (FK -> `vehicles.id` ON DELETE CASCADE)
- `maintenance_type_id` INT NULL (FK -> `maintenance_types.id` ON DELETE SET NULL)
- `source_service_id` INT NULL (FK -> `service_records.id` ON DELETE SET NULL)
- `title` VARCHAR(120) NOT NULL
- `next_service_date` DATE NULL
- `next_service_mileage` INT NULL
- `interval_days` INT NULL
- `interval_distance_km` INT NULL
- `status` VARCHAR(50) NOT NULL DEFAULT 'ACTIVE'
- `notes` TEXT NULL
- `completed_at` TIMESTAMP NULL DEFAULT NULL
- `cancelled_at` TIMESTAMP NULL DEFAULT NULL
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.7 Table: `notifications`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL (FK -> `users.id` ON DELETE CASCADE)
- `vehicle_id` INT NULL (FK -> `vehicles.id` ON DELETE CASCADE)
- `schedule_id` INT NULL (FK -> `maintenance_schedules.id` ON DELETE SET NULL)
- `type` VARCHAR(50) NOT NULL
- `title` VARCHAR(255) NOT NULL
- `message` TEXT NOT NULL
- `dedupe_key` VARCHAR(191) NULL UNIQUE
- `is_read` BOOLEAN NOT NULL DEFAULT 0
- `read_at` DATETIME NULL
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP

### 2.8 Table: `notification_settings`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL UNIQUE (FK -> `users.id` ON DELETE CASCADE)
- `maintenance_reminder` BOOLEAN NOT NULL DEFAULT 1
- `registration_reminder` BOOLEAN NOT NULL DEFAULT 1
- `service_due_reminder` BOOLEAN NOT NULL DEFAULT 1
- `overdue_reminder` BOOLEAN NOT NULL DEFAULT 1
- `reminder_days` INT NOT NULL DEFAULT 7
- `email_notifications` BOOLEAN NOT NULL DEFAULT 0
- `push_notifications` BOOLEAN NOT NULL DEFAULT 0
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.9 Table: `vehicle_preferences`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL UNIQUE (FK -> `users.id` ON DELETE CASCADE)
- `default_service_interval` INT NOT NULL DEFAULT 6
- `default_mileage_interval` INT NOT NULL DEFAULT 5000
- `preferred_service_provider` VARCHAR(255) NULL
- `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

### 2.10 Table: `audit_logs`
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NULL (FK -> `users.id` ON DELETE SET NULL)
- `actor_type` VARCHAR(50) NOT NULL DEFAULT 'USER'
- `action` VARCHAR(255) NOT NULL
- `table_name` VARCHAR(100) NULL
- `record_id` INT NULL
- `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP

### 2.11 Supporting Infrastructure Tables
- **`remember_tokens`:** Split-token authentication (`selector` VARCHAR(32) UNIQUE, `hashed_validator` VARCHAR(64), `expires_at` DATETIME, FK -> `users.id` ON DELETE CASCADE).
- **`password_resets`:** Cryptographic reset tokens (`email` VARCHAR(255), `token_hash` VARCHAR(64), `expires_at` DATETIME).
- **`realtime_events`:** WebSocket event queue (`channel`, `event`, `payload` LONGTEXT, `processed_at`).

