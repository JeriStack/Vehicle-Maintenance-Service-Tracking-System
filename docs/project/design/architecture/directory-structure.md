# Repository Directory Structure & PSR-4 Baseline
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ARC-DIR-001  
**Phase:** 3–6 — Architecture Baseline & Foundation Alignment  
**Authority:** VMS Complete Plan, Composer Configuration (`composer.json`)  
**Status:** Active Architecture Baseline  
**Last Updated:** 2026-11-28  

---

## 1. Current Application Directory Mapping

The VMS repository maintains backward compatibility with existing root-served modular PHP entry points while enforcing a clean separation between public web assets/front-controller (`public/`), PSR-4 application source code (`app/`), CLI tooling (`bin/`), and protected runtime storage (`storage/`, `logs/`).

```text
Vehicle-Maintenance-Service-Tracking-System/
│
├── .github/                 # CI workflows (ci.yml) and agent/skill definitions
├── actions/                 # Browser POST action handlers (auth, vehicles, services, admin, settings)
├── admin/                   # System Administrator views (dashboard, users, vehicles, services, audit logs)
├── app/                     # PSR-4 application source (`VMS\`) — HTTP denied via .htaccess
│   ├── Controllers/         # Front-controller HTTP controllers (HealthController)
│   ├── Core/                # App, Config, Csrf, Database, Env, MigrationRunner, Request, Response, Router, Session
│   ├── Middleware/          # RequireAuthentication, RequireRole, ValidateCsrfToken
│   ├── Models/              # Domain models (.gitkeep)
│   ├── Repositories/        # PDO repositories (.gitkeep)
│   ├── Security/            # AdminProvisioner, AdminSecurity, EmailNormalizer, OwnershipGuard, PasswordHasher, RateLimiter
│   ├── Services/            # Domain orchestration services (.gitkeep)
│   └── Support/             # Global helpers (helpers.php)
├── assets/                  # Root-served static assets (synced with public/assets/)
│   ├── css/                 # Stylesheets (style.css, dashboard.css, forms.css, responsive.css)
│   ├── js/                  # Client scripts (app.js, dashboard.js, notifications.js, password-toggle.js, services.js, vehicles.js)
│   └── images/              # Branding logo, auth wallpaper, icons/
├── auth/                    # Authentication views (login, register, logout, forgot/reset/change password)
├── bin/                     # CLI tools (console, process_reminders.php) — HTTP denied via .htaccess
├── config/                  # Legacy bootstrap & configuration files — HTTP denied via .htaccess
├── dashboard/               # Vehicle Owner main dashboard view
├── database/                # Schema snapshot, ordered migrations, seeders — HTTP denied via .htaccess
│   ├── migrations/          # Versioned incremental SQL migrations (`YYYYMMDDNNNNNN_*.sql`)
│   └── seeders/             # PSR-4 seeders (`Database\Seeders\MaintenanceTypeSeeder`)
├── docs/                    # Project governance, design, database, and security docs — HTTP denied via .htaccess
├── helpers/                 # Legacy utility helpers — HTTP denied via .htaccess
├── includes/                # Shared PHP layout templates and auth guards — HTTP denied via .htaccess
├── logs/                    # Legacy runtime logs (app.log, mail.log, websocket.log) — HTTP denied via .htaccess
├── maintenance/             # Maintenance schedule views (index, upcoming, due, overdue)
├── notifications/           # In-app notification inbox views and status endpoints
├── public/                  # Dedicated public web document root (front controller + static assets)
│   ├── assets/              # Public static assets (synced with assets/)
│   ├── .htaccess            # Front-controller rewrite rules and security headers
│   └── index.php            # Modern front controller entry point
├── reminders/               # Reminder listing and detail views
├── reports/                 # Cost analytics, monthly/yearly reports, and CSV export
├── services/                # Service record CRUD views
├── settings/                # Account profile, password, notification, and vehicle preference views
├── storage/                 # Protected runtime storage — HTTP denied via .htaccess
│   ├── backups/             # Database backup archives (ignored in git except .gitkeep)
│   ├── cache/               # Runtime cache
│   ├── logs/                # Modern application error logs
│   ├── sessions/            # Server-managed session storage
│   └── uploads/             # Protected upload staging area
├── tests/                   # Automated test suite — HTTP denied via .htaccess
│   ├── Integration/Database/# Disposable-DB integration tests
│   ├── Smoke/               # Foundation smoke tests
│   ├── Unit/                # Unit test classes
│   ├── bootstrap.php        # Autoloader & test bootstrap
│   ├── run_tests.php        # Zero-dependency CLI test runner
│   └── TestCase.php         # PHPUnit-compatible base test case
├── uploads/                 # User-uploaded documents (script execution disabled via .htaccess)
│   └── vehicle_documents/   # Uploaded vehicle documents
├── vehicles/                # Vehicle garage CRUD views
├── websocket/               # RFC 6455 WebSocket server daemon and test client — HTTP denied via .htaccess
│
├── .env.example             # Non-sensitive environment template (.env is gitignored)
├── .gitignore               # Version control ignore rules
├── .htaccess                # Root Apache access controls and security headers
├── composer.json            # Dependency manifest, scripts, and PSR-4 mappings
├── composer.lock            # Locked dev dependency versions
├── index.php                # Root legacy entry point and login/dashboard redirector
├── phpunit.xml              # PHPUnit configuration
├── README.md                # Project overview and quickstart guide
├── SETUP_NOTES.txt          # Installation and operational notes
├── start_websocket.bat      # Windows WebSocket launcher
└── start_websocket.sh       # Unix WebSocket launcher
```

---

## 2. Composer Autoloading & PSR-4 Configuration (`composer.json`)

```json
{
    "autoload": {
        "psr-4": {
            "VMS\\": "app/",
            "Database\\Seeders\\": "database/seeders/"
        },
        "files": [
            "app/Support/helpers.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    }
}
```

---

## 3. Directory Invariants & Security Controls

1. **Dual Entry Point Preservation:** Both `public/index.php` (dedicated web root) and root `index.php` (XAMPP subfolder deployments) are preserved and operational.
2. **Internal Directory Web-Access Denial:** All non-public directories (`app/`, `bin/`, `config/`, `database/`, `docs/`, `helpers/`, `includes/`, `logs/`, `storage/`, `tests/`, `websocket/`) contain a dedicated `.htaccess` denying direct HTTP requests on both Apache 2.4 (`Require all denied`) and Apache 2.2 (`Order allow,deny / Deny from all`).
3. **Upload Script-Execution Restriction:** `uploads/.htaccess` disables directory listing (`Options -Indexes -ExecCGI`), turns off PHP execution (`php_flag engine off`), and denies HTTP access to script/executable extensions.
4. **Asset Parity:** Static assets in `assets/` and `public/assets/` must remain synchronized so both root-served and `public/`-served deployments render identically.
5. **Clean-Clone Directory Preservation:** Runtime and scaffold directories (`logs/`, `storage/*`, `uploads/vehicle_documents/`, `app/Models/`, `app/Repositories/`, `app/Services/`, `assets/images/icons/`, `public/assets/images/icons/`) contain `.gitkeep` markers so fresh Git checkouts retain the complete structure.
