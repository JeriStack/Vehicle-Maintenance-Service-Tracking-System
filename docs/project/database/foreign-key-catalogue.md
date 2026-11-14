# Foreign Key Catalogue & Referential Integrity Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-004  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 2 Relationship Catalogue  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Relational Map & Foreign Key Matrix

This catalogue formally records the referential integrity rules, foreign key actions, and business justifications across all parent-child relationships.

| FK Constraint Name | Parent Table (PK) | Child Table (FK) | Foreign Key Column | ON DELETE | ON UPDATE | Referential Integrity & Business Justification |
|---|---|---|---|---|---|---|
| `fk_vehicles_user` | `users` (`id`) | `vehicles` (`user_id`) | `user_id` | `CASCADE` | `CASCADE` | Vehicles belong to an authenticated user account. Hard deletion of a user under data retention purges the vehicle. Ordinary vehicle deletion is soft-archival (`status = 'archived'`). |
| `fk_ns_user` | `users` (`id`) | `notification_settings` (`user_id`) | `user_id` | `CASCADE` | `CASCADE` | Enforces 1:1 user settings relationship. Purging user deletes preference profile. |
| `fk_vp_user` | `users` (`id`) | `vehicle_preferences` (`user_id`) | `user_id` | `CASCADE` | `CASCADE` | Enforces 1:1 user preference relationship. Purging user deletes preference profile. |
| `fk_notif_user` | `users` (`id`) | `notifications` (`user_id`) | `user_id` | `CASCADE` | `CASCADE` | In-app alerts target a specific user recipient. |
| `fk_audit_user` | `users` (`id`) | `audit_logs` (`user_id`) | `user_id` | `SET NULL` | `CASCADE` | **Preserves audit trail integrity:** Removing a user account does not delete administrative audit records; `user_id` is set to NULL while `actor_type = 'USER'` is preserved. |
| `fk_vml_user` | `users` (`id`) | `vehicle_mileage_logs` (`recorded_by_user_id`) | `recorded_by_user_id` | `SET NULL` | `CASCADE` | Preserves historical odometer entries if the recording user is removed. |
| `fk_sr_vehicle` | `vehicles` (`id`) | `service_records` (`vehicle_id`) | `vehicle_id` | `CASCADE` | `CASCADE` | Service invoices belong to a vehicle. Vehicle soft-archival preserves all records; hard purge cascades. |
| `fk_ms_vehicle` | `vehicles` (`id`) | `maintenance_schedules` (`vehicle_id`) | `vehicle_id` | `CASCADE` | `CASCADE` | Future maintenance schedules belong to a vehicle. |
| `fk_vml_vehicle` | `vehicles` (`id`) | `vehicle_mileage_logs` (`vehicle_id`) | `vehicle_id` | `CASCADE` | `CASCADE` | Mileage progression entries belong to a vehicle. |
| `fk_notif_vehicle` | `vehicles` (`id`) | `notifications` (`vehicle_id`) | `vehicle_id` | `CASCADE` | `CASCADE` | Notifications related to a vehicle are removed if the vehicle is hard-deleted. |
| `fk_sr_mt` | `maintenance_types` (`id`) | `service_records` (`maintenance_type_id`) | `maintenance_type_id` | `SET NULL` | `CASCADE` | **Preserves invoice integrity:** Deactivating or deleting a catalog service type sets `maintenance_type_id` to NULL without corrupting the historical transaction or dollar amount. |
| `fk_ms_mt` | `maintenance_types` (`id`) | `maintenance_schedules` (`maintenance_type_id`) | `maintenance_type_id` | `SET NULL` | `CASCADE` | Future maintenance schedule preserves title if catalog type is deactivated. |
| `fk_ms_source_srv` | `service_records` (`id`) | `maintenance_schedules` (`source_service_id`) | `source_service_id` | `SET NULL` | `CASCADE` | Tracks completed service provenance. Voiding or deleting the source invoice sets link to NULL. |
| `fk_notif_schedule` | `maintenance_schedules` (`id`) | `notifications` (`schedule_id`) | `schedule_id` | `SET NULL` | `CASCADE` | Fulfilling or cancelling a schedule sets foreign key to NULL while retaining the notification alert. |
| `fk_rt_user` | `users` (`id`) | `remember_tokens` (`user_id`) | `user_id` | `CASCADE` | `CASCADE` | Authentication session tokens are revoked immediately when a user account is removed. |

---

## 2. Archival vs Hard Deletion Policy

1. **Vehicles:** Vehicle deletion in the application UI executes a soft-archival operation (`status = 'archived'`). Under this policy, no cascading deletion takes place, guaranteeing that historical service invoices, cost tracking, and mileage logs remain 100% intact.
2. **Maintenance Types:** Catalog service items must never be hard-deleted in production; instead, administrators set `is_active = 0`. Even if a catalog item is removed, the `SET NULL` deletion action preserves existing service history.

