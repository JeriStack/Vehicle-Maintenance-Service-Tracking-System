# Comprehensive Data Dictionary
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-DD-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Database Schema, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Table: `users`
Stores authenticated user identities for vehicle owners and platform administrators.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `users` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique user identifier | Internal |
| `users` | `full_name` | `VARCHAR(255)` | No | None | None | - | Non-empty, max 255 chars | - | User's legal full name | Confidential |
| `users` | `email` | `VARCHAR(255)` | No | None | Unique | - | Valid email format, normalized lowercase | `idx_users_email` (UNIQUE) | Primary login identifier | Confidential |
| `users` | `password` | `VARCHAR(255)` | No | None | None | - | Bcrypt hash, min 60 chars | - | Cryptographic password hash | Security-Sensitive |
| `users` | `phone` | `VARCHAR(50)` | Yes | NULL | None | - | Phone digits / plus format | - | Contact phone number | Confidential |
| `users` | `role` | `ENUM('user','admin')` | No | `'user'` | None | - | Must be 'user' or 'admin' | `idx_users_role` | Application role | Internal |
| `users` | `status` | `ENUM('active','inactive')` | No | `'active'` | None | - | Must be 'active' or 'inactive' | `idx_users_status` | Account lifecycle state | Internal |
| `users` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Account creation timestamp | Internal |
| `users` | `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Last modification timestamp | Internal |

---

## 2. Table: `vehicles`
Stores registered vehicles owned by users.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `vehicles` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique vehicle identifier | Internal |
| `vehicles` | `user_id` | `INT` | No | None | FK | `users(id)` ON DELETE CASCADE | Must reference valid user | `idx_vehicles_user_id` | Owner identifier | Internal |
| `vehicles` | `plate_number` | `VARCHAR(50)` | No | None | None | - | Non-empty plate string | `idx_vehicles_plate` | Display license plate | Internal |
| `vehicles` | `normalized_plate`| `VARCHAR(50)` | Yes | NULL | None | - | Uppercase alphanumeric | `idx_vehicles_user_norm_plate` (UNIQUE) | Canonical plate for uniqueness | Internal |
| `vehicles` | `vehicle_type` | `VARCHAR(100)` | No | None | None | - | Sedan, SUV, Truck, etc. | - | Classification type | Public |
| `vehicles` | `make` | `VARCHAR(100)` | No | None | None | - | Non-empty string | `idx_vehicles_make_model` | Manufacturer brand (e.g. Toyota) | Public |
| `vehicles` | `model` | `VARCHAR(100)` | No | None | None | - | Non-empty string | `idx_vehicles_make_model` | Vehicle model (e.g. Vios) | Public |
| `vehicles` | `year` | `INT` | No | None | None | - | $1886 \le \text{Year} \le \text{Current}+1$ | - | Manufacturing model year | Public |
| `vehicles` | `color` | `VARCHAR(50)` | Yes | NULL | None | - | Text string | - | Exterior color | Public |
| `vehicles` | `vin_number` | `VARCHAR(100)` | Yes | NULL | None | - | Alphanumeric VIN | - | Vehicle Identification Number | Confidential |
| `vehicles` | `current_mileage`| `INT` | No | `0` | None | - | $\ge 0$, monotonic integer | - | Master odometer reading (KM) | Internal |
| `vehicles` | `registration_date`| `DATE` | Yes | NULL | None | - | Valid calendar date | - | Official initial registration date | Internal |
| `vehicles` | `registration_expiry`| `DATE` | No | None | None | - | Valid calendar date | `idx_vehicles_reg_expiry` | Expiry date of vehicle registration | Internal |
| `vehicles` | `status` | `VARCHAR(50)` | No | `'Active'` | None | - | 'Active' or 'archived' | `idx_vehicles_status` | Operational status | Internal |
| `vehicles` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Record creation timestamp | Internal |
| `vehicles` | `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Last modification timestamp | Internal |

---

## 3. Table: `maintenance_types`
Global catalog of standardized maintenance activities managed by administrators.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `maintenance_types` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique maintenance type ID | Internal |
| `maintenance_types` | `name` | `VARCHAR(100)` | No | None | None | - | Non-empty string | - | Service name (e.g. Oil Change) | Public |
| `maintenance_types` | `slug` | `VARCHAR(100)` | No | None | Unique | - | Lowercase alphanumeric kebab | `idx_mt_slug` (UNIQUE) | Machine-readable slug | Public |
| `maintenance_types` | `description` | `TEXT` | Yes | NULL | None | - | Text description | - | Standard procedure details | Public |
| `maintenance_types` | `default_days_interval`| `INT` | Yes | NULL | None | - | Positive integer (days) | - | Recommended calendar interval | Public |
| `maintenance_types` | `default_km_interval`| `INT` | Yes | NULL | None | - | Positive integer (km) | - | Recommended distance interval | Public |
| `maintenance_types` | `is_active` | `BOOLEAN` | No | `TRUE` | None | - | Boolean | `idx_mt_is_active` | Active catalog visibility | Public |
| `maintenance_types` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Catalog creation timestamp | Internal |
| `maintenance_types` | `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Last update timestamp | Internal |

---

## 4. Table: `service_records`
Permanent transactional log of completed vehicle maintenance services and invoices.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `service_records` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique service invoice ID | Internal |
| `service_records` | `vehicle_id` | `INT` | No | None | FK | `vehicles(id)` ON DELETE CASCADE | Must reference valid vehicle | `idx_sr_vehicle_id` | Parent vehicle reference | Internal |
| `service_records` | `maintenance_type_id`| `INT` | Yes | NULL | FK | `maintenance_types(id)` ON DELETE SET NULL | References catalog item | `idx_sr_mt_id` | Standard service type link | Internal |
| `service_records` | `service_type` | `VARCHAR(100)` | No | None | None | - | Non-empty string | `idx_sr_service_type` | Service type title | Public |
| `service_records` | `service_date` | `DATE` | No | None | None | - | $\le \text{Current Date}$ | `idx_sr_service_date` | Date service was performed | Internal |
| `service_records` | `mileage` | `INT` | No | None | None | - | $\ge 0$ | - | Odometer at time of service (KM) | Internal |
| `service_records` | `description` | `TEXT` | Yes | NULL | None | - | Text string | - | Diagnostic notes / work performed | Internal |
| `service_records` | `parts_replaced`| `TEXT` | Yes | NULL | None | - | Text string | - | Replacement parts breakdown | Internal |
| `service_records` | `service_provider`| `VARCHAR(255)`| Yes | NULL | None | - | Text string | - | Shop / mechanic name (Metadata) | Internal |
| `service_records` | `cost` | `DECIMAL(10,2)`| No | `0.00` | None | - | $\ge 0.00$, 2 decimal precision | `idx_sr_cost` | Total invoice cost in PHP ₱ | Confidential |
| `service_records` | `next_service_date`| `DATE` | Yes | NULL | None | - | Future calendar date | `idx_sr_next_date` | Projected next service date | Internal |
| `service_records` | `next_service_mileage`| `INT` | Yes | NULL | None | - | > current mileage | - | Projected next mileage target | Internal |
| `service_records` | `status` | `VARCHAR(50)` | No | `'Completed'` | None | - | 'Completed' or 'Voided' | `idx_sr_status` | Transaction status | Internal |
| `service_records` | `void_reason` | `VARCHAR(255)` | Yes | NULL | None | - | Mandatory if status = Voided | - | Audit reason for record voiding | Internal |
| `service_records` | `remarks` | `TEXT` | Yes | NULL | None | - | Text string | - | General remarks | Internal |
| `service_records` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Record creation timestamp | Internal |
| `service_records` | `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Last modification timestamp | Internal |

---

## 5. Table: `maintenance_schedules`
Future maintenance targets evaluated dynamically by the status calculation engine.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `maintenance_schedules` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique schedule ID | Internal |
| `maintenance_schedules` | `vehicle_id` | `INT` | No | None | FK | `vehicles(id)` ON DELETE CASCADE | Must reference valid vehicle | `idx_ms_vehicle_id` | Target vehicle reference | Internal |
| `maintenance_schedules` | `maintenance_type_id`| `INT` | Yes | NULL | FK | `maintenance_types(id)` ON DELETE SET NULL | References catalog item | `idx_ms_mt_id` | Catalog service type | Internal |
| `maintenance_schedules` | `source_service_id` | `INT` | Yes | NULL | FK | `service_records(id)` ON DELETE SET NULL | Prior service triggering this | `idx_ms_source_srv`| Service record provenance | Internal |
| `maintenance_schedules` | `next_service_date` | `DATE` | Yes | NULL | None | - | At least date or km required | `idx_ms_next_date` | Target maintenance date | Internal |
| `maintenance_schedules` | `next_service_mileage`| `INT`| Yes | NULL | None | - | At least date or km required | `idx_ms_next_km` | Target odometer reading (KM) | Internal |
| `maintenance_schedules` | `status` | `VARCHAR(50)` | No | `'ACTIVE'` | None | - | 'ACTIVE', 'COMPLETED', 'CANCELLED' | `idx_ms_status` | Lifecycle state | Internal |
| `maintenance_schedules` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Schedule creation timestamp | Internal |
| `maintenance_schedules` | `completed_at` | `TIMESTAMP` | Yes | NULL | None | - | Set when fulfilled | - | Fulfillment timestamp | Internal |

---

## 6. Table: `vehicle_mileage_logs`
Audit history of odometer progression for each vehicle.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `vehicle_mileage_logs` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique log entry ID | Internal |
| `vehicle_mileage_logs` | `vehicle_id` | `INT` | No | None | FK | `vehicles(id)` ON DELETE CASCADE | References parent vehicle | `idx_vml_vehicle_id` | Associated vehicle | Internal |
| `vehicle_mileage_logs` | `recorded_mileage` | `INT` | No | None | None | - | $\ge 0$ integer | `idx_vml_mileage` | Odometer reading (KM) | Internal |
| `vehicle_mileage_logs` | `source` | `VARCHAR(50)`| No | `'MANUAL'` | None | - | 'MANUAL', 'SERVICE', 'IMPORT' | - | Origin of odometer update | Internal |
| `vehicle_mileage_logs` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | `idx_vml_created_at` | Recording timestamp | Internal |

---

## 7. Table: `notifications`
In-app notification items and alert logs.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `notifications` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique notification ID | Internal |
| `notifications` | `user_id` | `INT` | No | None | FK | `users(id)` ON DELETE CASCADE | Target recipient | `idx_notif_user_id` | Owner recipient | Confidential |
| `notifications` | `vehicle_id` | `INT` | Yes | NULL | FK | `vehicles(id)` ON DELETE CASCADE | Contextual vehicle | `idx_notif_vehicle_id`| Associated vehicle | Internal |
| `notifications` | `schedule_id`| `INT` | Yes | NULL | FK | `maintenance_schedules(id)` ON DELETE SET NULL | Contextual schedule | - | Associated schedule | Internal |
| `notifications` | `type` | `VARCHAR(50)` | No | None | None | - | 'REMINDER', 'DUE', 'OVERDUE' | - | Alert severity classification | Internal |
| `notifications` | `title` | `VARCHAR(255)` | No | None | None | - | Non-empty string | - | Notification headline | Internal |
| `notifications` | `message` | `TEXT` | No | None | None | - | Non-empty text | - | Detailed alert body | Internal |
| `notifications` | `dedupe_key` | `VARCHAR(64)` | Yes | NULL | None | - | 64-char SHA-256 hash | `idx_notif_dedupe` | Deduplication key | Internal |
| `notifications` | `is_read` | `BOOLEAN` | No | `FALSE` | None | - | Boolean flag | `idx_notif_is_read` | Read / unread status | Internal |
| `notifications` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Valid timestamp | `idx_notif_created_at` | Notification dispatch time | Internal |

---

## 8. Table: `notification_settings`
User-configurable preferences governing automated reminder alerts.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `notification_settings` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique settings ID | Internal |
| `notification_settings` | `user_id` | `INT` | No | None | FK, Unique | `users(id)` ON DELETE CASCADE | Exactly 1 settings row per user | `idx_ns_user_id` (UNIQUE) | Owner user link | Internal |
| `notification_settings` | `maintenance_reminder`| `BOOLEAN` | No | `TRUE` | None | - | Boolean | - | Enable service reminders | Internal |
| `notification_settings` | `registration_reminder`| `BOOLEAN` | No | `TRUE` | None | - | Boolean | - | Enable registration alerts | Internal |
| `notification_settings` | `service_due_reminder` | `BOOLEAN` | No | `TRUE` | None | - | Boolean | - | Enable due-today alerts | Internal |
| `notification_settings` | `overdue_reminder` | `BOOLEAN` | No | `TRUE` | None | - | Boolean | - | Enable overdue alerts | Internal |
| `notification_settings` | `reminder_days` | `INT` | No | `7` | None | - | $1 \le \text{Days} \le 30$ | - | Advance reminder window | Internal |
| `notification_settings` | `email_notifications` | `BOOLEAN` | No | `FALSE`| None | - | Boolean | - | Enable SMTP email dispatch | Internal |
| `notification_settings` | `push_notifications` | `BOOLEAN` | No | `FALSE`| None | - | Boolean | - | Enable browser push | Internal |
| `notification_settings` | `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Last modification time | Internal |

---

## 9. Table: `vehicle_preferences`
User-level defaults for new vehicle creation and service intervals.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `vehicle_preferences` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique preference ID | Internal |
| `vehicle_preferences` | `user_id` | `INT` | No | None | FK, Unique | `users(id)` ON DELETE CASCADE | Exactly 1 preference row per user | `idx_vp_user_id` (UNIQUE) | Owner user link | Internal |
| `vehicle_preferences` | `default_service_interval` | `INT` | No | `6` | None | - | Months ($1 \le m \le 24$) | - | Default calendar interval | Internal |
| `vehicle_preferences` | `default_mileage_interval` | `INT` | No | `5000` | None | - | Kilometres ($1000 \le km \le 20000$) | - | Default distance interval | Internal |
| `vehicle_preferences` | `preferred_service_provider`| `VARCHAR(255)`| Yes | NULL | None | - | Text string | - | Default shop name | Internal |
| `vehicle_preferences` | `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | - | Valid timestamp | - | Last update timestamp | Internal |

---

## 10. Table: `audit_logs`
Immutable audit trail capturing administrative mutations, security events, and record modifications.

| Table | Column | SQL Type | Nullable | Default | Key | Reference | Validation | Index | Description | Sensitivity |
|---|---|---|---|---|---|---|---|---|---|---|
| `audit_logs` | `id` | `INT AUTO_INCREMENT` | No | None | PK | - | Positive integer | PRIMARY | Unique audit log ID | Internal |
| `audit_logs` | `user_id` | `INT` | Yes | NULL | FK | `users(id)` ON DELETE SET NULL | Actor user (NULL for CLI daemon) | `idx_audit_user_id` | Actor who performed action | Internal |
| `audit_logs` | `actor_type` | `VARCHAR(50)` | No | `'USER'` | None | - | 'USER', 'ADMIN', 'SYSTEM_DAEMON' | - | Execution actor category | Internal |
| `audit_logs` | `action` | `VARCHAR(255)` | No | None | None | - | Non-empty action description | `idx_audit_action` | Action descriptor | Internal |
| `audit_logs` | `table_name` | `VARCHAR(100)` | Yes | NULL | None | - | Affected database table | `idx_audit_table` | Target entity | Internal |
| `audit_logs` | `record_id` | `INT` | Yes | NULL | None | - | Positive integer | - | Target record identifier | Internal |
| `audit_logs` | `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | None | - | Immutable creation timestamp | `idx_audit_created_at` | Event timestamp | Internal |

