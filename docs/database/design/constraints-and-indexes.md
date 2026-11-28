# Constraints & Index Design Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-CI-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Database Schema, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Index Strategy Overview

The indexing design optimizes high-frequency query patterns—such as owner-scoped vehicle lookups, status calculation date ranges, notification inbox feeds, and deduplication sweeps—while avoiding unneeded index maintenance overhead during transactional writes.

---

## 2. Table-by-Table Index Definitions

| Table | Index Name | Type | Column(s) | Supported Query / Workload Pattern | Reason for Inclusion |
|---|---|---|---|---|---|
| `users` | `PRIMARY` | B-Tree | `id` | Row lookup by primary key | Entity identity |
| `users` | `idx_users_email` | B-Tree (UNIQUE) | `email` | `SELECT * FROM users WHERE email = ?` | Enforces email uniqueness and speeds up login lookups |
| `users` | `idx_users_role` | B-Tree | `role` | `SELECT COUNT(*) FROM users WHERE role = 'admin'` | Accelerates last-administrator count queries |
| `users` | `idx_users_status` | B-Tree | `status` | Active user sweeps during scheduled jobs | Quick filtering of active vs deactivated accounts |
| `vehicles` | `PRIMARY` | B-Tree | `id` | Row lookup by primary key | Entity identity |
| `vehicles` | `idx_vehicles_user_id` | B-Tree | `user_id` | `SELECT * FROM vehicles WHERE user_id = ?` | Essential for owner-scoped garage listings |
| `vehicles` | `idx_vehicles_user_norm_plate` | B-Tree (UNIQUE) | `user_id`, `normalized_plate` | `SELECT id FROM vehicles WHERE user_id = ? AND normalized_plate = ?` | Enforces owner-scoped plate uniqueness (`BR-VEH-02`) |
| `vehicles` | `idx_vehicles_reg_expiry` | B-Tree | `registration_expiry` | `WHERE registration_expiry <= DATE_ADD(CURRENT_DATE, ...)` | Optimizes daily registration expiry reminder sweeps |
| `vehicles` | `idx_vehicles_status` | B-Tree | `status` | `WHERE status != 'archived'` | Quickly excludes soft-archived vehicles |
| `vehicles` | `idx_vehicles_make_model` | B-Tree | `make`, `model` | Multi-column filter on owner garage | Fast brand and model filtering |
| `maintenance_types`| `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `maintenance_types`| `idx_mt_slug` | B-Tree (UNIQUE) | `slug` | `SELECT * FROM maintenance_types WHERE slug = ?` | Enforces unique URL/service type slugs |
| `maintenance_types`| `idx_mt_is_active` | B-Tree | `is_active` | `SELECT * FROM maintenance_types WHERE is_active = 1` | Speeds up active dropdown lists for service creation |
| `service_records` | `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `service_records` | `idx_sr_vehicle_id` | B-Tree | `vehicle_id` | `SELECT * FROM service_records WHERE vehicle_id = ?` | Critical for vehicle service history lists |
| `service_records` | `idx_sr_service_date` | B-Tree | `service_date` | `ORDER BY service_date DESC` | Accelerates chronological history ordering and date range queries |
| `service_records` | `idx_sr_status` | B-Tree | `status` | `WHERE status = 'Completed'` | Filters out voided records from cost sums |
| `service_records` | `idx_sr_next_date` | B-Tree | `next_service_date` | `WHERE next_service_date <= CURRENT_DATE` | Fast lookup of date-due maintenance |
| `service_records` | `idx_sr_composite_dash` | B-Tree | `vehicle_id`, `status`, `service_date` | Dashboard recent services summary queries | Composite index covering common dashboard aggregations |
| `maintenance_schedules`| `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `maintenance_schedules`| `idx_ms_vehicle_status`| B-Tree | `vehicle_id`, `status` | `WHERE vehicle_id = ? AND status = 'ACTIVE'` | Fetches active future schedules for a car |
| `maintenance_schedules`| `idx_ms_next_date` | B-Tree | `next_service_date` | Scheduled daemon threshold sweep | Fast scan of date-based maintenance triggers |
| `maintenance_schedules`| `idx_ms_next_km` | B-Tree | `next_service_mileage` | Scheduled daemon threshold sweep | Fast scan of distance-based maintenance triggers |
| `vehicle_mileage_logs`| `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `vehicle_mileage_logs`| `idx_vml_vehicle_id` | B-Tree | `vehicle_id`, `created_at` | `WHERE vehicle_id = ? ORDER BY created_at DESC` | Quick chronological odometer history retrieval |
| `notifications` | `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `notifications` | `idx_notif_user_read` | B-Tree | `user_id`, `is_read` | `WHERE user_id = ? AND is_read = 0` | Crucial for navbar unread counter and inbox badge queries |
| `notifications` | `idx_notif_dedupe` | B-Tree | `dedupe_key` | `WHERE dedupe_key = ? AND created_at > ...` | Enables rapid deduplication checks during background sweeps |
| `notification_settings`| `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `notification_settings`| `idx_ns_user_id` | B-Tree (UNIQUE) | `user_id` | `WHERE user_id = ?` | Enforces 1:1 relationship with user |
| `vehicle_preferences` | `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `vehicle_preferences` | `idx_vp_user_id` | B-Tree (UNIQUE) | `user_id` | `WHERE user_id = ?` | Enforces 1:1 relationship with user |
| `audit_logs` | `PRIMARY` | B-Tree | `id` | Primary key lookup | Entity identity |
| `audit_logs` | `idx_audit_user_id` | B-Tree | `user_id` | `WHERE user_id = ?` | Filters audit events by actor |
| `audit_logs` | `idx_audit_created_at`| B-Tree | `created_at` | `ORDER BY created_at DESC` | Powers chronological administrative audit feed |

---

## 3. Data Integrity & Check Constraints

1. **Email Format Constraint:** `email` must match valid RFC 5322 regex.
2. **Year Bounds Constraint:** $1886 \le \text{year} \le (\text{YEAR(CURRENT\_DATE)} + 1)$.
3. **Mileage Non-Negative Constraint:** `current_mileage >= 0` and `mileage >= 0`.
4. **Service Cost Constraint:** `cost >= 0.00` stored in `DECIMAL(10,2)`.
5. **Reminder Lead Days Constraint:** $1 \le \text{reminder\_days} \le 30$.

