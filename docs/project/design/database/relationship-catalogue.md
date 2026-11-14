# Relational Relationship Catalogue
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-RC-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Database Schema, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Relational Map & Cardinality Matrix

This catalogue formally records the referential integrity rules, cardinalities, optionality, and foreign key actions governing all inter-table relationships.

| Rel ID | Parent Entity (1) | Child Entity (N) | Foreign Key Column | Cardinality | Parent Optionality | Child Optionality | ON DELETE Action | ON UPDATE Action | Business & Architectural Justification |
|---|---|---|---|---|---|---|---|---|---|
| **REL-01** | `users` (`id`) | `vehicles` (`user_id`) | `user_id` | 1 : N | Mandatory | Optional | `CASCADE` | `CASCADE` | A vehicle must belong to an owner. If an account is permanently purged via data-retention, associated vehicles are removed. |
| **REL-02** | `users` (`id`) | `notification_settings` (`user_id`) | `user_id` | 1 : 1 | Mandatory | Mandatory | `CASCADE` | `CASCADE` | Exactly one notification configuration profile per registered user. |
| **REL-03** | `users` (`id`) | `vehicle_preferences` (`user_id`) | `user_id` | 1 : 1 | Mandatory | Mandatory | `CASCADE` | `CASCADE` | Exactly one servicing preference profile per registered user. |
| **REL-04** | `users` (`id`) | `notifications` (`user_id`) | `user_id` | 1 : N | Mandatory | Optional | `CASCADE` | `CASCADE` | Notifications are delivered to a specific user recipient. |
| **REL-05** | `users` (`id`) | `audit_logs` (`user_id`) | `user_id` | 1 : N | Optional | Optional | `SET NULL` | `CASCADE` | Preserves audit trail integrity: if a user is deleted, their historical audit events remain with `user_id = NULL` and `actor_type = 'USER'`. |
| **REL-06** | `vehicles` (`id`) | `service_records` (`vehicle_id`) | `vehicle_id` | 1 : N | Mandatory | Optional | `CASCADE` | `CASCADE` | Service records must be associated with a valid vehicle. Note: ordinary vehicle deletion uses soft-archival (`status = 'archived'`) to prevent firing this cascade. |
| **REL-07** | `vehicles` (`id`) | `maintenance_schedules` (`vehicle_id`) | `vehicle_id` | 1 : N | Mandatory | Optional | `CASCADE` | `CASCADE` | Future maintenance targets belong to a specific vehicle. |
| **REL-08** | `vehicles` (`id`) | `vehicle_mileage_logs` (`vehicle_id`) | `vehicle_id` | 1 : N | Mandatory | Optional | `CASCADE` | `CASCADE` | Odometer progression audit trail tracks a single vehicle. |
| **REL-09** | `vehicles` (`id`) | `notifications` (`vehicle_id`) | `vehicle_id` | 1 : N | Optional | Optional | `CASCADE` | `CASCADE` | Notifications generated in the context of a vehicle (e.g. registration alert) link to the vehicle. |
| **REL-10** | `maintenance_types` (`id`) | `service_records` (`maintenance_type_id`) | `maintenance_type_id` | 1 : N | Optional | Optional | `SET NULL` | `CASCADE` | Preserves service history: deactivating or deleting a catalog service type sets the foreign key to NULL without corrupting the invoice. |
| **REL-11** | `maintenance_types` (`id`) | `maintenance_schedules` (`maintenance_type_id`) | `maintenance_type_id` | 1 : N | Optional | Optional | `SET NULL` | `CASCADE` | Links future scheduled task to global template. |
| **REL-12** | `service_records` (`id`) | `maintenance_schedules` (`source_service_id`) | `source_service_id` | 1 : 1 | Optional | Optional | `SET NULL` | `CASCADE` | Tracks provenance: records which completed service event triggered the creation of the next schedule. |

