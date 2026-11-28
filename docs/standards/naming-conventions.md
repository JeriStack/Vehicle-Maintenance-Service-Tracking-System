# Unified Naming Conventions Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-NAM-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** PSR-1, PSR-12, Database Design (`VMS-DOC-DES-DD-001`)  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. PHP & Object-Oriented Naming

| Code Element | Naming Case | Suffix / Prefix Standard | Concrete Example |
|---|---|---|---|
| **Class Names** | `PascalCase` | Descriptive noun or noun phrase | `MaintenanceCalculator`, `DatabaseHelper` |
| **Interface Names** | `PascalCase` | Ending in `Interface` | `NotificationChannelInterface` |
| **Trait Names** | `PascalCase` | Ending in `Trait` | `TimestampableTrait` |
| **Class Methods** | `camelCase` | Active verb prefix | `calculateStatus()`, `verifyPassword()` |
| **Class Properties** | `camelCase` | Descriptive noun | `$currentMileage`, `$dbConnection` |
| **Local Variables** | `camelCase` | Clear descriptive context | `$vehicleRecord`, `$serviceCost` |
| **Constants** | `UPPER_SNAKE_CASE` | Global / class constants | `DEFAULT_COST_LIMIT`, `WS_PORT` |
| **Booleans** | `camelCase` | Prefix with `is`, `has`, `can`, `should` | `$isActive`, `$hasExceededThreshold` |
| **Test Classes** | `PascalCase` | Ending in `Test` | `MaintenanceCalculatorTest`, `AuthTest` |
| **Test Methods** | `camelCase` | `test{Method}_{Condition}_{Expected}` | `testCalculateStatus_MileageExceeded_ReturnsOverdue()` |

---

## 2. Database Naming Conventions

| Database Element | Naming Case | Plurality / Rules | Concrete Example |
|---|---|---|---|
| **Table Names** | `snake_case` | Plural nouns | `users`, `vehicles`, `service_records` |
| **Primary Keys** | `snake_case` | Standard `id` | `id` (`INT UNSIGNED AUTO_INCREMENT`) |
| **Foreign Keys** | `snake_case` | Singular parent table + `_id` | `user_id`, `vehicle_id`, `maintenance_type_id` |
| **Standard Columns**| `snake_case` | Descriptive singular nouns | `plate_number`, `current_mileage`, `service_date`|
| **Timestamps** | `snake_case` | Standard suffix `_at` | `created_at`, `updated_at`, `read_at` |
| **Date Fields** | `snake_case` | Standard suffix `_date` | `service_date`, `expiry_date`, `due_date` |
| **Boolean Flags** | `snake_case` | Prefix `is_` or `has_` | `is_read`, `is_active`, `is_archived` |
| **Unique Indexes** | `snake_case` | Prefix `uniq_{table}_{column}` | `uniq_users_email`, `uniq_vehicles_vin` |
| **Composite Indexes**| `snake_case`| Prefix `idx_{table}_{cols}` | `idx_vehicles_user_status` |

---

## 3. Web Routing & File Naming Conventions

| Architectural Component | File Naming Convention | Directory Location | Concrete Example |
|---|---|---|---|
| **View Pages** | `snake_case.php` | Dedicated module directories | `vehicles/index.php`, `vehicles/view.php` |
| **Action Handlers** | `snake_case_action.php` | `actions/` or `admin/actions/` | `actions/login_action.php`, `actions/service_action.php` |
| **Helpers / Singletons**| `PascalCase.php` | `helpers/` | `helpers/AuthHelper.php`, `helpers/MailHelper.php` |
| **Domain Services** | `PascalCase.php` | `app/Services/` | `app/Services/OdometerService.php` |
| **SQL Migrations** | `YYYYMMDDHHMMSS_{description}.sql` | `database/migrations/` | `20261105000001_create_maintenance_schedules.sql` |
| **Stylesheets** | `kebab-case.css` | `assets/css/` | `assets/css/style.css`, `assets/css/responsive.css` |
| **Client Scripts** | `kebab-case.js` | `assets/js/` | `assets/js/app.js`, `assets/js/notifications.js` |

