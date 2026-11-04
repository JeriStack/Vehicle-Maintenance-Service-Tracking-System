# Index Catalogue & Query Performance Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-005  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 2 Constraints & Index Specification  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Index Strategy Overview

The VMS indexing architecture accelerates high-frequency operational query patterns—such as owner garage listings, unread notification sweeps, date/odometer threshold checks, and authentication token lookups—while strictly avoiding duplicate or redundant index overhead.

---

## 2. Table-by-Table Index Inventory

| Table | Index Name | Type | Indexed Column(s) | Supported Application Query / Workload Pattern |
|---|---|---|---|---|
| `users` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `users` | `idx_users_email` | B-Tree (UNIQUE) | `email` | User login lookup: `SELECT * FROM users WHERE email = ?` |
| `users` | `idx_users_email_norm` | B-Tree (UNIQUE) | `email_normalized` | Case-insensitive duplicate registration prevention |
| `users` | `idx_users_role` | B-Tree | `role` | Admin count & role authorization filters |
| `users` | `idx_users_status` | B-Tree | `status` | Active user sweep during daily scheduled reminder jobs |
| `maintenance_types` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `maintenance_types` | `idx_mt_code` | B-Tree (UNIQUE) | `code` | Stable immutable catalog matching during seed/API operations |
| `maintenance_types` | `idx_mt_slug` | B-Tree (UNIQUE) | `slug` | Slug-based catalog URL routing |
| `maintenance_types` | `idx_mt_is_active` | B-Tree | `is_active` | Service record creation dropdown (`WHERE is_active = 1`) |
| `vehicles` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `vehicles` | `idx_vehicles_owner_plate_norm` | B-Tree (UNIQUE) | `user_id, plate_number_normalized` | Owner-scoped plate uniqueness (`BR-VEH-02`) |
| `vehicles` | `idx_vehicles_user_id` | B-Tree | `user_id` | Owner garage listings: `SELECT * FROM vehicles WHERE user_id = ?` |
| `vehicles` | `idx_vehicles_reg_expiry` | B-Tree | `registration_expiry` | Daily background scheduler sweep for expiring registrations |
| `vehicles` | `idx_vehicles_status` | B-Tree | `status` | Fast exclusion of soft-archived vehicles (`WHERE status != 'archived'`) |
| `vehicles` | `idx_vehicles_make_model` | B-Tree | `make, model` | Multi-column garage search and brand filtering |
| `vehicle_mileage_logs` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `vehicle_mileage_logs` | `idx_vml_vehicle_rec` | B-Tree | `vehicle_id, recorded_at` | Chronological odometer audit trail per vehicle |
| `vehicle_mileage_logs` | `idx_vml_mileage` | B-Tree | `mileage` | Odometer progression range queries |
| `service_records` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `service_records` | `idx_sr_vehicle_id` | B-Tree | `vehicle_id` | Vehicle service history listing |
| `service_records` | `idx_sr_service_date` | B-Tree | `service_date` | Chronological invoice history and date-range reporting |
| `service_records` | `idx_sr_status` | B-Tree | `status` | Exclusion of voided transactions from expense totals |
| `service_records` | `idx_sr_mt_id` | B-Tree | `maintenance_type_id` | Filter service records by catalog maintenance type |
| `service_records` | `idx_sr_next_date` | B-Tree | `next_service_date` | Quick scan of legacy date-due maintenance |
| `service_records` | `idx_sr_composite_dash` | B-Tree | `vehicle_id, status, service_date` | Composite covering index for dashboard summary widgets |
| `maintenance_schedules`| `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `maintenance_schedules`| `idx_ms_vehicle_status`| B-Tree | `vehicle_id, status` | Active future schedule listing per vehicle |
| `maintenance_schedules`| `idx_ms_next_date` | B-Tree | `next_service_date` | Background scheduler sweep for date-due maintenance |
| `maintenance_schedules`| `idx_ms_next_km` | B-Tree | `next_service_mileage` | Background scheduler sweep for odometer-due maintenance |
| `maintenance_schedules`| `idx_ms_mt_id` | B-Tree | `maintenance_type_id` | Catalog item reference filter |
| `notifications` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `notifications` | `idx_notif_dedupe` | B-Tree (UNIQUE) | `dedupe_key` | Absolute prevention of duplicate alerts during automated sweeps |
| `notifications` | `idx_notif_user_read` | B-Tree | `user_id, is_read` | Topbar unread badge counter: `WHERE user_id = ? AND is_read = 0` |
| `notifications` | `idx_notif_created_at`| B-Tree | `created_at` | Notification inbox chronological feed |
| `notifications` | `idx_notif_vehicle_id`| B-Tree | `vehicle_id` | Vehicle-specific alert filter |
| `notification_settings`| `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `notification_settings`| `idx_ns_user_id` | B-Tree (UNIQUE) | `user_id` | Enforces 1:1 user settings profile |
| `vehicle_preferences` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `vehicle_preferences` | `idx_vp_user_id` | B-Tree (UNIQUE) | `user_id` | Enforces 1:1 user vehicle preference profile |
| `audit_logs` | `PRIMARY` | B-Tree (PK) | `id` | Primary key identity lookup |
| `audit_logs` | `idx_audit_user_id` | B-Tree | `user_id` | Security audit search by actor user |
| `audit_logs` | `idx_audit_action` | B-Tree | `action` | Audit log action filtering |
| `audit_logs` | `idx_audit_table` | B-Tree | `table_name` | Audit log target table filtering |
| `audit_logs` | `idx_audit_created_at`| B-Tree | `created_at` | Administrative audit trail chronological display |
| `remember_tokens` | `idx_rt_selector` | B-Tree (UNIQUE) | `selector` | O(1) lookup of split-token remember-me cookie selector |
| `remember_tokens` | `idx_rt_expires_at` | B-Tree | `expires_at` | Background cleanup sweep of expired session tokens |
| `password_resets` | `idx_pr_email` | B-Tree | `email` | Lookup of pending password reset attempts by email |
| `password_resets` | `idx_pr_token` | B-Tree | `token_hash` | Validation of submitted reset token hash |
| `realtime_events` | `idx_re_processed` | B-Tree | `processed_at` | Daemon sweep for unprocessed WebSocket broadcast events |
