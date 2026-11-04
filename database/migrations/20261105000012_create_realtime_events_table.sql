-- Migration: 20261105000012_create_realtime_events_table.sql
-- Description: Creates persistent realtime events buffer for WebSocket broadcasting.

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
