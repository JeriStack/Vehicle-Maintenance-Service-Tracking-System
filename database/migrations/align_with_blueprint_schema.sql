-- database/migrations/align_with_blueprint_schema.sql
-- Aligns database schema with the official VMS Blueprint & UML Specifications
USE vehicle_maintenance_db;

-- 1. Global Maintenance Types Catalog
CREATE TABLE IF NOT EXISTS maintenance_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    default_interval_days INT NULL,
    default_interval_distance_km INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed default global maintenance types if empty
INSERT IGNORE INTO maintenance_types (id, name, description, default_interval_days, default_interval_distance_km, is_active) VALUES
(1, 'Oil & Filter Change', 'Engine oil replacement and new oil filter installation', 180, 5000, 1),
(2, 'Tire Rotation & Balance', 'Rotate and balance all four tires to ensure even tread wear', 180, 10000, 1),
(3, 'Brake Inspection & Service', 'Inspect brake pads, rotors, drums, and brake fluid lines', 365, 20000, 1),
(4, 'Air & Cabin Filter Replacement', 'Replace engine air filter and cabin air ventilation filter', 365, 15000, 1),
(5, 'Battery & Charging Test', 'Inspect battery terminals, charge voltage, and alternator output', 180, 10000, 1),
(6, 'Coolant / Radiator Flush', 'Drain, flush, and refill engine coolant and check radiator hoses', 730, 40000, 1),
(7, 'Spark Plug Replacement', 'Replace spark plugs and inspect ignition coils/cables', 730, 40000, 1),
(8, 'Transmission Fluid Service', 'Drain and refill transmission fluid, inspect gaskets and seals', 1095, 60000, 1);

-- 2. Maintenance Schedules (Separates future planning from historical completed work)
CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_type_id INT NULL,
    title VARCHAR(120) NOT NULL,
    last_service_record_id INT NULL,
    due_date DATE NULL,
    due_odometer_km INT UNSIGNED NULL,
    interval_days INT NULL,
    interval_distance_km INT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (maintenance_type_id) REFERENCES maintenance_types(id) ON DELETE SET NULL,
    FOREIGN KEY (last_service_record_id) REFERENCES service_records(id) ON DELETE SET NULL,
    INDEX (vehicle_id, status),
    INDEX (status, due_date)
);

-- 3. Vehicle Mileage Logs (Monotonic odometer audit trail)
CREATE TABLE IF NOT EXISTS vehicle_mileage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_record_id INT NULL,
    odometer_reading_km INT UNSIGNED NOT NULL,
    source ENUM('manual', 'service') DEFAULT 'manual',
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (service_record_id) REFERENCES service_records(id) ON DELETE SET NULL,
    INDEX (vehicle_id, recorded_at)
);

-- 4. Alter notifications table to add deduplication key and read_at timestamp if missing
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS dedupe_key VARCHAR(191) NULL UNIQUE;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS read_at DATETIME NULL;

