# Vehicle Maintenance Service Tracking System

A full-stack, production-ready PHP application to track, manage, and report vehicle maintenance records.

## Features

- **Authentication & Security:** Secure login/registration with password hashing (bcrypt). Prevention against Session Fixation, CSRF attacks, XSS, and IDOR.
- **Dashboard:** Interactive dashboard showing overview metrics, unread notifications, and quick access to recent services.
- **Vehicle Management:** Add, edit, view, and delete vehicles with details like VIN, license plate, color, and year.
- **Service Records:** Track completed and upcoming services, including costs, mechanic details, and replacement parts.
- **Smart Maintenance Tracking:** Automatically determines if a service is Upcoming, Due, or Overdue based on dates or mileage logic.
- **Notifications & Reminders:** Configurable background engine to alert users of expiring registrations and due services.
- **Advanced Reporting:** Summaries, cost tracking (monthly/yearly), service history logs, and CSV exports.
- **Responsive UI:** Built with Bootstrap 5, Bootstrap Icons, and Chart.js for a modern, mobile-friendly interface.

## Tech Stack

- **Backend:** Native PHP 8+, PDO MySQL/MariaDB with PSR-4 autoloading (`VMS\`)
- **Database:** MySQL / MariaDB (managed via `bin/console migrate` and seeders)
- **Frontend:** HTML5, CSS3, Vanilla JS, Bootstrap 5, Chart.js
- **Real-Time:** Native RFC 6455 WebSocket Server (PHP socket stream daemon) with fallback polling
- **Architecture:** Front controller in `public/index.php`, lightweight router, middleware, centralized PDO, and zero-framework design

## Directory Layout

```text
Vehicle-Maintenance-Service-Tracking-System/
├── app/                      # Application core (PSR-4 VMS\)
│   ├── Controllers/          # HTTP request controllers (HealthController, etc.)
│   ├── Core/                 # Framework engine (App, Router, Request, Response, Database, Config, Session, Csrf, Env, MigrationRunner)
│   ├── Middleware/           # HTTP middleware (AuthMiddleware, AdminMiddleware, CsrfMiddleware)
│   ├── Models/               # Domain data models
│   ├── Repositories/         # Database persistence abstractions
│   ├── Services/             # Domain business logic services
│   └── Support/              # Global helpers (helpers.php: e(), csrf_token(), flash(), old())
├── bin/                      # CLI tools and console runner (bin/console)
├── config/                   # Configuration presets (database, constants, websocket, mail)
├── database/                 # Database schema, migrations, and seeders
│   ├── migrations/           # Versioned SQL schema migrations (20261105000001–13)
│   ├── seeders/              # Database reference data seeders (MaintenanceTypeSeeder)
│   └── schema.sql            # Clean relational schema snapshot
├── public/                   # Dedicated public web document root
│   ├── assets/               # Public static assets (CSS, JS, icons)
│   ├── .htaccess             # Apache rewrite rules and security headers
│   └── index.php             # Unified front controller entry point
├── storage/                  # Protected application storage (outside web root)
│   ├── logs/                 # Centralized structured application logs
│   ├── sessions/             # Server-side session files
│   ├── uploads/              # Vehicle documents and attachments
│   └── backups/              # Database backups and exports
├── tests/                    # Automated testing suite
│   ├── Smoke/                # End-to-end foundation smoke tests (SmokeTest.php)
│   ├── Unit/                 # Comprehensive business logic unit tests
│   ├── Integration/          # Dedicated database integration tests (DatabaseIntegrationTest.php)
│   └── run_tests.php         # Zero-dependency native CLI test runner
└── docs/                     # Engineering, architecture, and governance documentation
```

## Project Governance & Documentation

Formal engineering deliverables and project management baselines are organized in [`docs/`](docs/README.md):
- **Governance (Phase 0):** [Project Charter](docs/governance/project-charter.md), [Scope Statement](docs/governance/scope-statement.md), [Environment Baseline](docs/governance/environment-baseline.md), [Phase 0 Assessment](docs/assessments/phase-0-assessment.md).
- **Requirements & Backlog (Phase 1):** [Requirements Baseline](docs/requirements/requirements-baseline.md), [Product Backlog](docs/backlog/product-backlog.md), [Traceability Matrix](docs/requirements/traceability-matrix.md), [Phase 1 Assessment](docs/assessments/phase-1-assessment.md).
- **Architecture & Design (Phases 2–3):** [Application Architecture](docs/architecture/application-architecture.md), [Architecture Decisions (ADRs)](docs/architecture/architecture-decision-records.md), [Process Analysis](docs/architecture/process-analysis.md), [Route Map](docs/architecture/routing/route-map.md), [Design Tokens](docs/ui-ux/design-tokens.md), [Wireframes](docs/ui-ux/wireframes/), [Engineering Standards](docs/standards/), [Phase 2](docs/assessments/phase-2-assessment.md) & [Phase 3 Assessments](docs/assessments/phase-3-assessment.md).
- **Application Foundation (Phase 4):** [Directory Structure](docs/architecture/directory-structure.md), [Phase 4 Assessment](docs/assessments/phase-4-assessment.md).
- **Database (Phases 2 & 5):** [Data Dictionary](docs/database/design/data-dictionary.md), [Migration Inventory](docs/database/implementation/migration-inventory.md), [Clean Installation Guide](docs/database/implementation/clean-installation-guide.md), [Existing Database Upgrade](docs/database/implementation/existing-database-upgrade.md), [Phase 5 Assessment](docs/assessments/phase-5-assessment.md).
- **Security (Phases 2 & 6):** [STRIDE Threat Model](docs/security/threat-model.md), [Authentication Implementation](docs/security/authentication-implementation.md), [Authorization Policy](docs/security/authorization-policy.md), [Session Management](docs/security/session-management.md), [CSRF Protection](docs/security/csrf-protection.md).

## Installation

Follow these steps to set up the development environment:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/JeriStack/Vehicle-Maintenance-Service-Tracking-System.git
   cd Vehicle-Maintenance-Service-Tracking-System
   ```

2. **Install Composer dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment settings:**
   Copy the example environment configuration file and adjust local database credentials:
   ```bash
   cp .env.example .env
   ```

4. **Create the MySQL/MariaDB database:**
   ```sql
   CREATE DATABASE vehicle_maintenance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Run database migrations:**
   Execute all registered schema migrations through the console utility:
   ```bash
   php bin/console migrate
   ```

6. **Seed baseline reference data:**
   Populate initial vehicle maintenance types and system categories:
   ```bash
   php bin/console seed
   ```

7. **Configure web server document root:**
   - **Production / Apache / XAMPP:** Configure your virtual host or Apache DocumentRoot to point directly to the `public/` directory:
     ```apache
     DocumentRoot "c:/xampp/htdocs/GitHub_Clone_File_Projects/Vehicle-Maintenance-Service-Tracking-System/public"
     ```
   - **Local PHP Development Server:**
     ```bash
     php -S localhost:8000 -t public/
     ```

8. **Verify installation & health:**
   - Run the automated test suite:
     ```bash
     php tests/run_tests.php
     ```
   - Run the database integration suite:
     ```bash
     php tests/run_tests.php --database
     ```
   - Run the console health probe:
     ```bash
     php bin/console health
     ```
   - Access the health endpoints in your browser or curl:
     - Liveness: `http://localhost:8000/health`
     - Readiness: `http://localhost:8000/health/ready`

9. **(Optional) Run Real-Time WebSocket Server:**
   - On Windows: Run `php websocket/server.php` or double-click `start_websocket.bat`.
   - On Linux/macOS: Run `php websocket/server.php` or `./start_websocket.sh`.

## Configuration

If you change the folder name, update the `BASE_URL` in `config/constants.php`:

```php
define('BASE_URL', '/Your-Folder-Name/');
```

Database credentials can be updated in `config/database.php` or `.env`.

## Testing

Run the automated test suite with:

```bash
# Run unit and smoke tests (zero external dependencies)
php tests/run_tests.php

# Run database integration tests (against disposable test database)
php tests/run_tests.php --database

# Run complete test suite (Unit, Smoke, Database)
php tests/run_tests.php --all

# Or using Composer
composer test
composer run test:db
composer run test:all
```

## Continuous Integration

GitHub Actions runs on pushes and pull requests. The workflow checks PHP syntax on PHP 8.0 and 8.5, runs the unit test suite, and verifies that `database/schema.sql` imports into MySQL 8.0.

## Author
Developed as an enterprise-grade PHP native application.
