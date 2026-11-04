# Repository Directory Structure & PSR-4 Baseline
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ARC-DIR-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Composer Configuration (`composer.json`)  
**Status:** Approved Architecture Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Existing Application Directory Mapping

The current VMS repository organizes application functionality using direct functional PHP modules, centralized helper classes, and dedicated assets. This structure is preserved during Phase 3 to avoid disruptive changes to active deployment configurations.

```text
Vehicle-Maintenance-Service-Tracking-System/
│
├── .github/                 # Workflows and agent definitions
├── actions/                 # Vehicle Owner POST action handlers (CRUD, auth, logs)
├── admin/                   # System Administrator views and administrative actions
│   └── actions/             # Administrator state-changing handlers (users, catalog, roles)
├── assets/                  # Public client-side assets
│   ├── css/                 # Stylesheets (style.css, forms.css, responsive.css)
│   ├── js/                  # JavaScript clients (app.js, notifications.js, vehicles.js)
│   └── images/              # Branding logo, icons, auth wallpaper
├── auth/                    # Unauthenticated authentication views (login, register, forgot)
├── config/                  # Configuration files (database.php, config.php, session.php)
├── dashboard/               # Vehicle Owner main dashboard view
├── database/                # Schema definitions, seeders, and migration scripts
│   └── migrations/          # Versioned incremental SQL migration files
├── helpers/                 # Core utility and domain singleton classes (Auth, DB, Mail, WS)
├── includes/                # Shared layout templates (header.php, sidebar.php, footer.php)
├── logs/                    # Application runtime logs, error logs, audit logs
├── maintenance/             # Maintenance schedule views and interval tracking
├── notifications/           # In-app notification inbox views and AJAX pollers
├── reminders/               # Headless CLI scheduler daemon scripts (cron_reminders.php)
├── reports/                 # Cost analytics, spend summary views, and export handlers
├── services/                # Service history views, logging forms, and invoice viewers
├── settings/                # Account profile, password change, and preference forms
├── tests/                   # Automated test runner and PHPUnit test cases
│   ├── Unit/                # Unit test classes
│   └── run_tests.php        # Zero-dependency CLI test runner script
├── uploads/                 # User-uploaded receipt images and invoices (NoExec protected)
├── vehicles/                # Vehicle garage listing, detail views, and edit forms
├── websocket/               # RFC 6455 WebSocket socket server daemon and test clients
│
├── .gitignore               # Version control ignore rules
├── .htaccess                # Apache routing, security headers, and rewrite directives
├── composer.json            # Dependency manifest and autoloader mappings
├── index.php                # Root entry point and portal role router
├── phpunit.xml              # PHPUnit test configuration
├── README.md                # Project README and quickstart guide
├── SETUP_NOTES.txt          # Technical installation notes and operational guide
├── start_websocket.bat      # Windows batch script to launch WebSocket daemon
└── start_websocket.sh       # Unix shell script to launch WebSocket daemon
```

---

## 2. Composer Autoloading & Target PSR-4 Baseline

### 2.1 Existing Autoload Configuration (`composer.json`)
Currently, `composer.json` defines test autoloading via:
```json
{
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```
Existing helper classes in [`helpers/`](file:///c:/xampp/htdocs/GitHub_Clone_File_Projects/Vehicle-Maintenance-Service-Tracking-System/helpers/) are loaded via direct `require_once` declarations across module views and action scripts.

### 2.2 Approved Target PSR-4 Namespace Structure (Phase 4 Incremental Adoption)
To transition smoothly from global procedural helpers to structured object-oriented architecture without breaking existing endpoints, Phase 4 will introduce an incremental PSR-4 autoloader mapping:

```json
{
    "autoload": {
        "psr-4": {
            "VMS\\": "app/"
        },
        "classmap": [
            "helpers/"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

### 2.3 Target `app/` Logical Directory Organization:
When new domain services and repositories are implemented in subsequent sprints, they will reside in the structured `app/` directory:
- `app/Domain/`: Pure business entities and value objects (e.g., `Vehicle`, `ServiceRecord`, `MaintenanceSchedule`).
- `app/Services/`: Orchestration and calculation services (e.g., `MaintenanceCalculator`, `OdometerAdvancementService`).
- `app/Repositories/`: PDO database access classes (e.g., `VehicleRepository`, `UserRepository`, `AuditRepository`).
- `app/Middleware/`: Request lifecycle filters (e.g., `AuthMiddleware`, `RoleMiddleware`, `CsrfMiddleware`).
- `app/Validators/`: Input validation and sanitization classes.

---

## 3. Directory Invariants & Safety Constraints

1. **Root `index.php` Entry Preservation:** The root `index.php` will **not** be relocated to `public/index.php` during this phase, preventing disruption to existing XAMPP Apache `DocumentRoot` configurations.
2. **Upload Directory Execution Restriction:** The `uploads/` directory must contain a dedicated `.htaccess` file prohibiting script execution (`php_flag engine off`, `Options -ExecCGI`).
3. **Log Directory Security:** The `logs/` directory is blocked from direct web retrieval via `.htaccess` (`Require all denied`).

