# Application Route Map
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-RM-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Route Map Overview

This document catalogs every web page, POST action handler, AJAX endpoint, WebSocket port, and CLI command across the platform.

```text
Execution Contexts:
- GUEST: Unauthenticated web visitor
- VEHICLE_OWNER: Authenticated vehicle owner (role = 'user')
- SYSTEM_ADMIN: Authenticated system administrator (role = 'admin')
- CLI_SCHEDULER: Headless CLI command runner
```

---

## 2. Route Inventory

| Route ID | Method | Path | Module | Purpose | Access Context | Required Role | Owner Scoped? | CSRF Req? | Audit Req? | Implementation Status |
|---|---|---|---|---|---|---|---|---|---|---|
| **R-AUTH-01** | GET | `/index.php` | Root | Portal landing & role router | GUEST / ALL | None | No | No | No | **IMPLEMENTED** |
| **R-AUTH-02** | GET | `/auth/login.php` | Auth | Render login card | GUEST | None | No | No | No | **IMPLEMENTED** |
| **R-AUTH-03** | POST| `/actions/login_action.php`| Auth | Authenticate credentials & session | GUEST | None | No | Yes | Yes | **IMPLEMENTED** |
| **R-AUTH-04** | GET | `/auth/register.php` | Auth | Render user registration form | GUEST | None | No | No | No | **IMPLEMENTED** |
| **R-AUTH-05** | POST| `/actions/register_action.php`| Auth| Process account registration | GUEST | None | No | Yes | Yes | **IMPLEMENTED** |
| **R-AUTH-06** | GET | `/auth/forgot_password.php`| Auth| Request password reset link | GUEST | None | No | No | No | **IMPLEMENTED** |
| **R-AUTH-07** | POST| `/actions/forgot_password_action.php`| Auth| Generate & email reset token | GUEST | None | No | Yes | Yes | **IMPLEMENTED** |
| **R-AUTH-08** | GET | `/auth/reset_password.php`| Auth | Render new password input form | GUEST | None | No | No | No | **IMPLEMENTED** |
| **R-AUTH-09** | POST| `/actions/reset_password_action.php`| Auth| Update password with reset token| GUEST | None | No | Yes | Yes | **IMPLEMENTED** |
| **R-AUTH-10** | GET | `/auth/logout.php` | Auth | Invalidate session & tokens | ALL | None | No | No | Yes | **IMPLEMENTED** |
| **R-DASH-01** | GET | `/dashboard/index.php` | Dash | Owner dashboard & counters | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-VEH-01** | GET | `/vehicles/index.php` | Vehicle| Display active personal garage | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-VEH-02** | GET | `/vehicles/create.php` | Vehicle| Render vehicle registration form| VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-VEH-03** | GET | `/vehicles/view.php` | Vehicle| Detailed view of single vehicle | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-VEH-04** | GET | `/vehicles/edit.php` | Vehicle| Render vehicle edit form | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-VEH-05** | POST| `/actions/vehicle_action.php`| Vehicle| Handle create/update/archive | VEHICLE_OWNER | `user` | Yes | Yes | Yes | **IMPLEMENTED** |
| **R-SRV-01** | GET | `/services/index.php` | Service| View personal service history | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-SRV-02** | GET | `/services/create.php` | Service| Render service logging form | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-SRV-03** | GET | `/services/view.php` | Service| View single service invoice | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-SRV-04** | GET | `/services/edit.php` | Service| Render service edit form | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-SRV-05** | POST| `/actions/service_action.php`| Service| Process create/update/void | VEHICLE_OWNER | `user` | Yes | Yes | Yes | **IMPLEMENTED** |
| **R-MTN-01** | GET | `/maintenance/index.php` | Mtn | Overview of status calculations | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-MTN-02** | GET | `/maintenance/upcoming.php`| Mtn | View upcoming scheduled tasks | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-MTN-03** | GET | `/maintenance/due.php` | Mtn | View due maintenance tasks | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-MTN-04** | GET | `/maintenance/overdue.php`| Mtn | View overdue maintenance tasks | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-NTF-01** | GET | `/notifications/index.php` | Notif | In-app notification feed | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-NTF-02** | POST| `/notifications/mark_read.php`| Notif | Mark notification as read (AJAX)| VEHICLE_OWNER | `user` | Yes | Yes | No | **IMPLEMENTED** |
| **R-NTF-03** | POST| `/notifications/delete.php`| Notif | Dismiss / delete notification | VEHICLE_OWNER | `user` | Yes | Yes | No | **IMPLEMENTED** |
| **R-RPT-01** | GET | `/reports/index.php` | Report | Reports navigation hub | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-RPT-02** | GET | `/reports/maintenance.php` | Report | Maintenance summary metrics | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-RPT-03** | GET | `/reports/costs.php` | Report | Expense trend charts (Chart.js) | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-RPT-04** | GET | `/reports/service_history.php`| Report| Chronological service log | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-RPT-05** | GET | `/reports/export_csv.php` | Report | Download CSV data export | VEHICLE_OWNER | `user` | Yes | No | Yes | **IMPLEMENTED** |
| **R-SET-01** | GET | `/settings/index.php` | Settings| User profile & settings hub | ALL AUTH | `user`/`admin`| Yes | No | No | **IMPLEMENTED** |
| **R-SET-02** | GET | `/settings/profile.php` | Settings| View/edit personal profile | ALL AUTH | `user`/`admin`| Yes | No | No | **IMPLEMENTED** |
| **R-SET-03** | POST| `/actions/profile_action.php`| Settings| Update profile details | ALL AUTH | `user`/`admin`| Yes | Yes | Yes | **IMPLEMENTED** |
| **R-SET-04** | GET | `/settings/password.php` | Settings| Render password change form | ALL AUTH | `user`/`admin`| Yes | No | No | **IMPLEMENTED** |
| **R-SET-05** | GET | `/settings/notifications.php`| Settings| Configure reminder toggles | VEHICLE_OWNER | `user` | Yes | No | No | **IMPLEMENTED** |
| **R-SET-06** | POST| `/actions/settings_action.php`| Settings| Update reminder preferences | VEHICLE_OWNER | `user` | Yes | Yes | Yes | **IMPLEMENTED** |
| **R-ADM-01** | GET | `/admin/dashboard.php` | Admin | Administrative overview metrics | SYSTEM_ADMIN | `admin` | No (Platform)| No | No | **IMPLEMENTED** |
| **R-ADM-02** | GET | `/admin/users.php` | Admin | User accounts directory & search| SYSTEM_ADMIN | `admin` | No (Platform)| No | No | **IMPLEMENTED** |
| **R-ADM-03** | GET | `/admin/user_view.php` | Admin | View single user account details| SYSTEM_ADMIN | `admin` | No (Platform)| No | No | **IMPLEMENTED** |
| **R-ADM-04** | POST| `/actions/admin_user_action.php`| Admin| Role switch / status toggle | SYSTEM_ADMIN | `admin` | No (Platform)| Yes | Yes | **IMPLEMENTED** |
| **R-ADM-05** | GET | `/admin/audit_logs.php` | Admin | Filterable security audit logs | SYSTEM_ADMIN | `admin` | No (Platform)| No | No | **IMPLEMENTED** |
| **R-ADM-06** | GET | `/admin/maintenance_types.php`| Admin| Global service catalog UI | SYSTEM_ADMIN | `admin` | No (Platform)| No | No | **PROPOSED (P1)** |
| **R-CLI-01** | CLI | `php bin/process_reminders.php`| CLI Daemon| Headless reminder processor | CLI_SCHEDULER | None (CLI) | All Active | No | Yes | **IMPLEMENTED** |
| **R-CLI-02** | CLI | `php websocket/server.php` | CLI Daemon| Native RFC 6455 socket daemon | CLI_SCHEDULER | None (CLI) | N/A | No | Yes | **IMPLEMENTED** |
| **R-CLI-03** | CLI | `php tests/run_tests.php` | CLI Test | Automated unit test runner | CLI_SCHEDULER | None (CLI) | N/A | No | No | **IMPLEMENTED** |

