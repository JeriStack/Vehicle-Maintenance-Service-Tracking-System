-- Migration: 20261105000011_create_auth_tokens_tables.sql
-- Description: Creates remember-me authentication tokens and password reset requests tables.

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

