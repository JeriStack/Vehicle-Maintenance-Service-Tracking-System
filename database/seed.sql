-- database/seed.sql
USE vehicle_maintenance_db;

-- Clear existing data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE audit_logs;
TRUNCATE TABLE vehicle_preferences;
TRUNCATE TABLE notification_settings;
TRUNCATE TABLE notifications;
TRUNCATE TABLE maintenance_reminders;
TRUNCATE TABLE service_records;
TRUNCATE TABLE vehicles;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Demo Users (Password is 'password123' for all)
-- Password hashed using password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (id, full_name, email, password, phone) VALUES 
(1, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890'),
(2, 'Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321');

-- Notification Settings & Preferences
INSERT INTO notification_settings (user_id) VALUES (1), (2);
INSERT INTO vehicle_preferences (user_id) VALUES (1), (2);

-- Demo Vehicles
INSERT INTO vehicles (id, user_id, plate_number, vehicle_type, make, model, year, color, vin_number, current_mileage, registration_date, registration_expiry, status) VALUES
(1, 1, 'ABC-1234', 'Sedan', 'Toyota', 'Vios', 2022, 'White', 'VIN123456789', 45320, '2022-01-10', DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY), 'Active'),
(2, 1, 'XYZ-5678', 'SUV', 'Honda', 'CR-V', 2021, 'Black', 'VIN987654321', 60000, '2021-05-20', DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 'Active'),
(3, 2, 'DEF-9012', 'Hatchback', 'Mazda', '3', 2020, 'Red', 'VIN456123789', 80500, '2020-03-15', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 'Active');

-- Demo Service Records
-- 1. Completed
INSERT INTO service_records (id, vehicle_id, service_type, service_date, mileage, description, parts_replaced, service_provider, cost, next_service_date, next_service_mileage, status) VALUES
(1, 1, 'Oil Change', DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH), 40000, 'Regular oil change', 'Oil filter, Engine oil', 'Toyota Service Center', 150.00, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 45000, 'Completed'),
(2, 2, 'Tire Rotation', DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH), 58000, 'Tire rotation and alignment', '', 'Quick Lane', 80.00, DATE_ADD(CURRENT_DATE, INTERVAL 4 MONTH), 68000, 'Completed');

-- 2. Upcoming / Due / Overdue mapped via dates
-- Next oil change for vehicle 1 is overdue (was due 5 days ago)
-- Next brake check for vehicle 2 is upcoming (due in 20 days)
INSERT INTO service_records (id, vehicle_id, service_type, service_date, mileage, description, parts_replaced, service_provider, cost, next_service_date, next_service_mileage, status) VALUES
(3, 1, 'Brake Check', DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR), 30000, 'Replaced brake pads', 'Brake pads', 'Toyota Service Center', 250.00, DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 50000, 'Completed');

-- Notifications
INSERT INTO notifications (user_id, vehicle_id, type, title, message, is_read) VALUES
(1, 1, 'OVERDUE', 'Service Overdue', 'Oil Change is overdue for Toyota Vios (ABC-1234).', 0),
(1, 2, 'REMINDER', 'Registration Expiring Soon', 'Registration for Honda CR-V (XYZ-5678) expires in 15 days.', 0),
(2, 3, 'OVERDUE', 'Registration Expired', 'Registration for Mazda 3 (DEF-9012) has expired!', 0);

-- Audit Logs
INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES
(1, 'User registered', 'users', 1),
(2, 'User registered', 'users', 2),
(1, 'Added vehicle', 'vehicles', 1),
(1, 'Added vehicle', 'vehicles', 2),
(2, 'Added vehicle', 'vehicles', 3);
