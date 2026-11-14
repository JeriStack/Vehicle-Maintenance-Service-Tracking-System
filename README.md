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

Formal engineering deliverables and project management baselines are located in [`docs/project/`](docs/project/):
- **Phase 0 — Project Initiation & Feasibility:** [Project Charter](docs/project/project-charter.md), [Scope Statement](docs/project/scope-statement.md), [Environment Baseline](docs/project/environment-baseline.md), [Phase 0 Assessment](docs/project/phase-0-assessment.md).
- **Phase 1 — Requirements & Backlog:** [Requirements Baseline](docs/project/requirements/requirements-baseline.md), [Product Backlog](docs/project/backlog/product-backlog.md), [Traceability Matrix](docs/project/requirements/traceability-matrix.md), [Phase 1 Assessment](docs/project/phase-1-assessment.md).
- **Phase 2 — Process Analysis & Design:** [Process Analysis](docs/project/design/process-analysis.md), [Architecture Decisions (ADRs)](docs/project/design/architecture-decision-records.md), [Data Dictionary](docs/project/design/database/data-dictionary.md), [Route Map](docs/project/design/routing/route-map.md), [Threat Model](docs/project/design/security/threat-model.md), [Phase 2 Assessment](docs/project/phase-2-assessment.md).
- **Phase 3 — UI/UX Planning & Architecture Baseline:** [Design Tokens](docs/project/design/ui-ux/design-tokens.md), [Component Inventory](docs/project/design/ui-ux/component-inventory.md), [Wireframes Suite](docs/project/design/wireframes/), [Application Architecture](docs/project/design/architecture/application-architecture.md), [Engineering Standards](docs/project/design/standards/), [Phase 3 Assessment](docs/project/phase-3-assessment.md).
- **Phase 4 — Repository and Application Foundation (Sprint 1):** [Phase 4 Assessment & Foundation Report](docs/project/phase-4-assessment.md).
- **Phase 5 — Database Schema, Migrations, and Seed Data (Sprint 1):** [Phase 5 Database Assessment](docs/project/database/phase-5-assessment.md), [Migration Inventory](docs/project/database/migration-inventory.md), [Clean Installation Guide](docs/project/database/clean-installation-guide.md), [Existing Database Upgrade](docs/project/database/existing-database-upgrade.md).

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
