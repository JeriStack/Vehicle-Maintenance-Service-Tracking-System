-- Migration: 20261105000001_create_users_table.sql
-- Description: Creates the users table supporting two application roles (user/admin), normalized email uniqueness, and account status.

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_normalized` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_users_email` (`email`),
    UNIQUE KEY `idx_users_email_norm` (`email_normalized`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

