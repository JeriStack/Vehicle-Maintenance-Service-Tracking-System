-- database/seed.sql
-- Vehicle Maintenance Service Tracking System (VMS)
-- Demo Dataset Seeder (Phase 5/6 Schema Compatible)
--
-- Pre-seeded Demo Accounts:
--   Demo Vehicle Owner:   demo@example.com  / password123
--   System Administrator: admin@example.com / password123

USE vehicle_maintenance_db;

-- Clear existing demo/transactional data safely
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `audit_logs`;
TRUNCATE TABLE `login_attempts`;
TRUNCATE TABLE `remember_tokens`;
TRUNCATE TABLE `password_resets`;
TRUNCATE TABLE `realtime_events`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `maintenance_schedules`;
TRUNCATE TABLE `service_records`;
TRUNCATE TABLE `vehicle_mileage_logs`;
TRUNCATE TABLE `vehicle_preferences`;
TRUNCATE TABLE `notification_settings`;
TRUNCATE TABLE `vehicles`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- Baseline Maintenance Types Catalog (idempotent)
INSERT INTO `maintenance_types` (`id`, `code`, `name`, `slug`, `description`, `default_interval_days`, `default_interval_distance_km`, `is_active`) VALUES
(1, 'oil-change', 'Oil & Filter Change', 'oil-change', 'Engine oil replacement and new oil filter installation', 180, 5000, 1),
(2, 'tire-rotation', 'Tire Rotation & Balance', 'tire-rotation', 'Rotate and balance all four tires to ensure even tread wear', 180, 10000, 1),
(3, 'brake-check', 'Brake Inspection & Service', 'brake-check', 'Inspect brake pads, rotors, drums, and brake fluid lines', 365, 20000, 1),
(4, 'battery-replacement', 'Battery Replacement & Test', 'battery-replacement', 'Inspect battery terminals, charge voltage, and alternator output', 730, 20000, 1),
(5, 'engine-service', 'Engine Service & Tune-up', 'engine-service', 'Replace engine spark plugs, inspect filters, and diagnostic check', 365, 15000, 1),
(6, 'transmission-service', 'Transmission Fluid Service', 'transmission-service', 'Drain and refill transmission fluid, inspect gaskets and seals', 1095, 60000, 1),
(7, 'vehicle-inspection', 'Comprehensive Vehicle Inspection', 'vehicle-inspection', 'Multi-point safety inspection covering chassis, suspension, lights, and steering', 365, 20000, 1),
(8, 'other', 'Other Maintenance', 'other', 'General vehicle repair, custom servicing, or miscellaneous bodywork', NULL, NULL, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `slug` = VALUES(`slug`),
    `description` = VALUES(`description`),
    `default_interval_days` = VALUES(`default_interval_days`),
    `default_interval_distance_km` = VALUES(`default_interval_distance_km`),
    `is_active` = 1;

-- Demo Users (Password is 'password123' hashed with bcrypt cost 12)
INSERT INTO `users` (`id`, `full_name`, `email`, `email_normalized`, `password`, `phone`, `role`, `status`, `auth_version`) VALUES
(1, 'Demo Vehicle Owner', 'demo@example.com', 'demo@example.com', '$2y$12$j2rt18dxmqaBEyqgfHB2tevMQSnVcIWxsZ.3Fpo9mQ91usPioH6VW', '1234567890', 'user', 'active', 1),
(2, 'System Administrator', 'admin@example.com', 'admin@example.com', '$2y$12$j2rt18dxmqaBEyqgfHB2tevMQSnVcIWxsZ.3Fpo9mQ91usPioH6VW', '0987654321', 'admin', 'active', 1);

-- 1:1 Notification Settings & Vehicle Preferences
INSERT INTO `notification_settings` (`user_id`) VALUES (1), (2);
INSERT INTO `vehicle_preferences` (`user_id`) VALUES (1), (2);

-- Demo Vehicles (with normalized plate numbers)
INSERT INTO `vehicles` (`id`, `user_id`, `plate_number`, `plate_number_normalized`, `vehicle_type`, `make`, `model`, `year`, `color`, `vin_number`, `current_mileage`, `registration_date`, `registration_expiry`, `status`) VALUES
(1, 1, 'ABC-1234', 'ABC1234', 'Sedan', 'Toyota', 'Vios', 2022, 'White', 'VIN123456789', 45320, '2022-01-10', DATE_ADD(CURRENT_DATE, INTERVAL 45 DAY), 'Active'),
(2, 1, 'XYZ-5678', 'XYZ5678', 'SUV', 'Honda', 'CR-V', 2021, 'Black', 'VIN987654321', 60000, '2021-05-20', DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 'Active'),
(3, 2, 'DEF-9012', 'DEF9012', 'Hatchback', 'Mazda', '3', 2020, 'Red', 'VIN456123789', 80500, '2020-03-15', DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 'Active');

-- Demo Vehicle Mileage Logs
INSERT INTO `vehicle_mileage_logs` (`vehicle_id`, `mileage`, `source`, `recorded_by_user_id`) VALUES
(1, 40000, 'SERVICE_RECORD', 1),
(1, 45320, 'MANUAL', 1),
(2, 58000, 'SERVICE_RECORD', 1),
(2, 60000, 'MANUAL', 1),
(3, 80500, 'MANUAL', 2);

-- Demo Service Records
INSERT INTO `service_records` (`id`, `vehicle_id`, `maintenance_type_id`, `service_type`, `service_date`, `mileage`, `description`, `parts_replaced`, `service_provider`, `cost`, `next_service_date`, `next_service_mileage`, `status`) VALUES
(1, 1, 1, 'Oil Change', DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH), 40000, 'Regular oil change', 'Oil filter, Engine oil', 'Toyota Service Center', 150.00, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 45000, 'Completed'),
(2, 2, 2, 'Tire Rotation', DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH), 58000, 'Tire rotation and alignment', '', 'Quick Lane', 80.00, DATE_ADD(CURRENT_DATE, INTERVAL 4 MONTH), 68000, 'Completed'),
(3, 1, 3, 'Brake Check', DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR), 30000, 'Replaced brake pads', 'Brake pads', 'Toyota Service Center', 250.00, DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 50000, 'Completed');

-- Demo Maintenance Schedules (Overdue & Upcoming examples)
INSERT INTO `maintenance_schedules` (`id`, `vehicle_id`, `maintenance_type_id`, `source_service_id`, `title`, `next_service_date`, `next_service_mileage`, `interval_days`, `interval_distance_km`, `status`) VALUES
(1, 1, 1, 1, 'Oil & Filter Change', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 45000, 180, 5000, 'ACTIVE'),
(2, 2, 2, 2, 'Tire Rotation & Balance', DATE_ADD(CURRENT_DATE, INTERVAL 4 MONTH), 68000, 180, 10000, 'ACTIVE'),
(3, 1, 3, 3, 'Brake Inspection & Service', DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 50000, 365, 20000, 'ACTIVE');

-- Demo Notifications
INSERT INTO `notifications` (`user_id`, `vehicle_id`, `schedule_id`, `type`, `title`, `message`, `is_read`) VALUES
(1, 1, 1, 'OVERDUE', 'Service Overdue', 'Oil Change is overdue for Toyota Vios (ABC-1234).', 0),
(1, 2, NULL, 'REMINDER', 'Registration Expiring Soon', 'Registration for Honda CR-V (XYZ-5678) expires in 15 days.', 0),
(2, 3, NULL, 'OVERDUE', 'Registration Expired', 'Registration for Mazda 3 (DEF-9012) has expired!', 0);

-- Demo Audit Logs
INSERT INTO `audit_logs` (`user_id`, `actor_type`, `action`, `table_name`, `record_id`) VALUES
(1, 'USER', 'User registered', 'users', 1),
(2, 'USER', 'User registered', 'users', 2),
(1, 'USER', 'Added vehicle', 'vehicles', 1),
(1, 'USER', 'Added vehicle', 'vehicles', 2),
(2, 'USER', 'Added vehicle', 'vehicles', 3);
