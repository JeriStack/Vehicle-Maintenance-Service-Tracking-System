-- database/migrations/create_realtime_events_table.sql
USE vehicle_maintenance_db;

CREATE TABLE IF NOT EXISTS realtime_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    channel VARCHAR(100) NOT NULL,
    event VARCHAR(100) NOT NULL,
    payload LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX (processed_at),
    INDEX (channel),
    INDEX (created_at)
);

