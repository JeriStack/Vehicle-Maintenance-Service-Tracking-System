# Normalization & Constraints Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-006  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 1 Requirements & Phase 2 Data Dictionary  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Business Normalization Rules

To prevent data corruption, evasion of unique checks, and display inconsistencies, the database implements canonical normalization at the physical persistence layer.

### 1.1 Normalized Email (`email_normalized`)
- **Canonical Rule:** Trim surrounding whitespace, normalize to lowercase string:
  $$\text{email\_normalized} = \text{LOWER}(\text{TRIM}(\text{email}))$$
- **Enforcement:** Enforced via `UNIQUE KEY idx_users_email_norm (email_normalized)`.
- **Evasion Defense:** Prevents dual registrations where a user registers `user@example.com` and later `User@Example.com` or ` user@example.com `.
- **Display Preservation:** The original display format entered by the user is preserved in `email` for polite customer communications.

### 1.2 Owner-Scoped Normalized Plate (`plate_number_normalized`)
- **Canonical Rule:** Trim whitespace, strip internal spaces and hyphens, convert to uppercase alphanumeric:
  $$\text{plate\_number\_normalized} = \text{UPPER}(\text{REPLACE}(\text{REPLACE}(\text{plate\_number}, \text{' '}, \text{''}), \text{'-'}, \text{''}))$$
- **Enforcement:** Enforced via `UNIQUE KEY idx_vehicles_owner_plate_norm (user_id, plate_number_normalized)`.
- **Business Logic (`BR-VEH-02`):**
  - **Same Owner:** Registration of `ABC-1234` and `ABC 1234` or `abc1234` by the same owner is **rejected**.
  - **Different Owners:** Registration of `ABC-1234` by Owner A and Owner B (e.g. transfer of ownership or independent user testing) is **permitted**. A global unique plate constraint is strictly prohibited.
- **Display Preservation:** The human-formatted plate (`ABC-1234`) is preserved in `plate_number`.

### 1.3 Notification Deduplication Key (`dedupe_key`)
- **Canonical Rule:** Deterministic 64-character SHA-256 hash incorporating the schedule/reminder event identity, recipient, category, and date threshold:
  $$\text{dedupe\_key} = \text{SHA256}(\text{CONCAT}(\text{category}, \text{':'}, \text{target\_id}, \text{':'}, \text{trigger\_date}))$$
- **Enforcement:** Enforced via `UNIQUE KEY idx_notif_dedupe (dedupe_key)`.
- **Scheduler Defense:** Repeated background sweeps by `process_reminders.php` can never produce duplicate in-app alerts for the same maintenance trigger.

---

## 2. Integrity & Check Constraints

| Constraint Name | Target Table | Expression / Rule | Purpose |
|---|---|---|---|
| Non-negative Current Mileage | `vehicles` | `current_mileage >= 0` | Odometer readings cannot be negative. |
| Non-negative Mileage Log | `vehicle_mileage_logs` | `mileage >= 0` | Historical mileage readings cannot be negative. |
| Non-negative Service Mileage | `service_records` | `mileage >= 0` | Service transaction odometer cannot be negative. |
| Non-negative Service Cost | `service_records` | `cost >= 0.00` | Invoices cannot have negative costs; currency uses `DECIMAL(10,2)`. |
| Maintenance Trigger Existence | `maintenance_schedules` | `next_service_date IS NOT NULL OR next_service_mileage IS NOT NULL` | Every future schedule must have at least one valid trigger (calendar date or odometer reading). |
| Reminder Lead Days Range | `notification_settings` | `reminder_days >= 1 AND reminder_days <= 30` | Advance reminder alert window is constrained between 1 and 30 days. |
| Vehicle Service Interval | `vehicle_preferences` | `default_service_interval >= 1 AND default_service_interval <= 24` | Default calendar interval constrained between 1 and 24 months. |
| Vehicle Mileage Interval | `vehicle_preferences` | `default_mileage_interval >= 1000 AND default_mileage_interval <= 20000` | Default distance interval constrained between 1,000 and 20,000 km. |

