-- Migration: 20261105000008_create_notification_settings_table.sql
-- Description: Creates per-user notification preferences table with 1:1 user constraint.

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
