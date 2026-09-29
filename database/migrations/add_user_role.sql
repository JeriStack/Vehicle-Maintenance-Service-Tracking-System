-- database/migrations/add_user_role.sql
USE vehicle_maintenance_db;

-- Add role column safely
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER phone;
