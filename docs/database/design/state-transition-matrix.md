# State Transition Matrix
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-STM-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. State Models Overview

This document specifies all valid lifecycle states, permitted transitions, authorized actors, transition guards (preconditions), side effects, and audit requirements across the platform's stateful entities.

---

## 2. Transition Matrices

### 2.1 User Account State Model (`users.status`)

| Initial State | Permitted Next State | Trigger / Action | Authorized Actor | Preconditions / Guards | System Side Effects | Audit Log Action |
|---|---|---|---|---|---|---|
| *None* | `active` | Self-Registration | Guest | Valid email, password $\ge 6$ chars | Default role assigned (`user`); settings rows created | `User registered` |
| `active` | `inactive` | Deactivate Account | System Admin | Target user is not sole active admin | Active sessions invalidated; remember tokens cleared | `Deactivated user account` |
| `inactive` | `active` | Reactivate Account | System Admin | Account previously inactive | User permitted to authenticate again | `Reactivated user account` |
| `active` / `inactive` | *Purged* | Anonymization Purge | System Admin | Formal Right-to-Forgotten request | PII scrubbed; numeric stats retained | `Anonymized user account` |

---

### 2.2 Vehicle Lifecycle State Model (`vehicles.status`)

| Initial State | Permitted Next State | Trigger / Action | Authorized Actor | Preconditions / Guards | System Side Effects | Audit Log Action |
|---|---|---|---|---|---|---|
| *None* | `Active` | Add Vehicle | Vehicle Owner | Valid year, non-negative odometer, plate | Inserted into garage; initial mileage logged | `Added vehicle` |
| `Active` | `archived` | Archive Vehicle | Vehicle Owner | Vehicle owned by caller (`user_id` matches) | Excluded from active garage; past services preserved | `Archived vehicle` |
| `archived` | `Active` | Restore Vehicle | Vehicle Owner | Plate does not conflict with active garage | Re-enters active garage grid; schedules re-activated | `Restored vehicle` |

---

### 2.3 Service Record State Model (`service_records.status`)

| Initial State | Permitted Next State | Trigger / Action | Authorized Actor | Preconditions / Guards | System Side Effects | Audit Log Action |
|---|---|---|---|---|---|---|
| *None* | `Completed` | Log Service | Vehicle Owner | Service date $\le$ today; cost $\ge 0$ | Advances vehicle odometer; creates mileage log | `Added service record` |
| `Completed` | `Voided` | Void Record | Vehicle Owner | Record owned by caller; non-empty void reason | Excluded from financial sums; history retains row | `Voided service record` |

---

### 2.4 Maintenance Schedule State Model (`maintenance_schedules.status`)

| Initial State | Permitted Next State | Trigger / Action | Authorized Actor | Preconditions / Guards | System Side Effects | Audit Log Action |
|---|---|---|---|---|---|---|
| *None* | `ACTIVE` | Schedule Task | Vehicle Owner | Valid date or km trigger provided | Included in daily reminder daemon sweeps | `Created maintenance schedule` |
| `ACTIVE` | `COMPLETED` | Service Performed | Vehicle Owner | Corresponding completed service logged | `completed_at` populated; stops triggering alerts | `Completed maintenance schedule` |
| `ACTIVE` | `CANCELLED` | Dismiss Schedule | Vehicle Owner | Task deemed no longer applicable | Stops triggering alerts; historical record kept | `Cancelled maintenance schedule` |

> [!NOTE]
> **Dynamic Status Calculation:** `OVERDUE`, `DUE`, `UPCOMING`, and `NOT_DUE` are **calculated dynamically in real time** based on current calendar date and vehicle odometer. They are not stored as static database column states because dates and mileage continuously change.

---

### 2.5 Notification State Model (`notifications.is_read`)

| Initial State | Permitted Next State | Trigger / Action | Authorized Actor | Preconditions / Guards | System Side Effects | Audit Log Action |
|---|---|---|---|---|---|---|
| *None* | `UNREAD` (`0`) | Reminder Trigger | CLI Daemon / System | Threshold reached; dedupe key check passes | Notification inserted; WebSocket push sent | None |
| `UNREAD` (`0`) | `READ` (`1`) | User Clicks Notification | Vehicle Owner | Notification owned by authenticated user | Navbar unread counter decremented | None |
| `READ` (`1`) / `UNREAD` (`0`) | *Deleted* | Dismiss Notification | Vehicle Owner | Notification owned by authenticated user | Row removed from active feed | None |

---

### 2.6 Maintenance Type State Model (`maintenance_types.is_active`)

| Initial State | Permitted Next State | Trigger / Action | Authorized Actor | Preconditions / Guards | System Side Effects | Audit Log Action |
|---|---|---|---|---|---|---|
| *None* | `ACTIVE` (`1`) | Create Service Type | System Admin | Unique slug; non-empty name | Item available in service logging dropdowns | `Created maintenance type` |
| `ACTIVE` (`1`) | `INACTIVE` (`0`)| Deactivate Type | System Admin | Admin privileges confirmed | Hidden from new forms; past services unaffected | `Deactivated maintenance type` |
| `INACTIVE` (`0`)| `ACTIVE` (`1`)| Reactivate Type | System Admin | Admin privileges confirmed | Available in service logging dropdowns again | `Reactivated maintenance type` |

