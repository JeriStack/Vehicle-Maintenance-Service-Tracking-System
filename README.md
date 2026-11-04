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

- **Backend:** Native PHP 8+, PDO
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JS, Bootstrap 5, Chart.js
- **Real-Time:** Native RFC 6455 WebSocket Server (PHP socket stream daemon) with fallback polling

## Project Governance & Documentation

Formal engineering deliverables and project management baselines are located in [`docs/project/`](docs/project/):
- **Phase 0 — Project Initiation & Feasibility:** [Project Charter](docs/project/project-charter.md), [Scope Statement](docs/project/scope-statement.md), [Environment Baseline](docs/project/environment-baseline.md), [Phase 0 Assessment](docs/project/phase-0-assessment.md).
- **Phase 1 — Requirements & Backlog:** [Requirements Baseline](docs/project/requirements/requirements-baseline.md), [Product Backlog](docs/project/backlog/product-backlog.md), [Traceability Matrix](docs/project/requirements/traceability-matrix.md), [Phase 1 Assessment](docs/project/phase-1-assessment.md).
- **Phase 2 — Process Analysis & Design:** [Process Analysis](docs/project/design/process-analysis.md), [Architecture Decisions (ADRs)](docs/project/design/architecture-decision-records.md), [Data Dictionary](docs/project/design/database/data-dictionary.md), [Route Map](docs/project/design/routing/route-map.md), [Threat Model](docs/project/design/security/threat-model.md), [Phase 2 Assessment](docs/project/phase-2-assessment.md).
- **Phase 3 — UI/UX Planning & Architecture Baseline:** [Design Tokens](docs/project/design/ui-ux/design-tokens.md), [Component Inventory](docs/project/design/ui-ux/component-inventory.md), [Wireframes Suite](docs/project/design/wireframes/), [Application Architecture](docs/project/design/architecture/application-architecture.md), [Engineering Standards](docs/project/design/standards/), [Phase 3 Assessment](docs/project/phase-3-assessment.md).

## Installation

1. Clone or download the repository to your XAMPP `htdocs` directory.
   ```
   htdocs/Vehicle-Maintenance-Service-Tracking-System
   ```
2. Open phpMyAdmin or your MySQL client and create a new database:
   ```sql
   CREATE DATABASE vehicle_maintenance_db;
   ```
3. Import the database schemas:
   - Import `database/schema.sql` to build the required tables.
   - (Optional) Import `database/seed.sql` to populate sample data.
4. Ensure the `logs` directory has write permissions so the application can log errors.
5. Ensure `uploads/vehicle_documents/` is writable if you plan to store uploaded vehicle documents.
6. Configure the database connection in `config/database.php` and adjust `BASE_URL` in `config/constants.php` if the folder name changes.
7. Access the application in your browser:
   `http://localhost/Vehicle-Maintenance-Service-Tracking-System/`
8. (Optional) Run the Real-Time WebSocket server:
   - On Windows: Double-click `start_websocket.bat` or run `php websocket/server.php`.
   - On Linux/macOS: Run `./start_websocket.sh` or `php websocket/server.php`.

## Configuration

If you change the folder name, update the `BASE_URL` in `config/constants.php`:

```php
define('BASE_URL', '/Your-Folder-Name/');
```

Database credentials can be updated in `config/database.php`.

## Testing

Run the automated unit test suite with:

```bash
# Using native PHP runner (zero external dependencies)
php tests/run_tests.php

# Or using Composer
composer test
```

Tests cover validation rules, date and service due calculations, currency formatting, authentication states, and RFC 6455 WebSocket framing.

## Continuous integration

GitHub Actions runs on pushes and pull requests. The workflow checks PHP syntax on PHP 8.0 and 8.5, runs the unit test suite, and verifies that `database/schema.sql` imports into MySQL 8.0. It does not deploy the application.

## Author
Developed as a complete Phase 1-38 implementation for a robust PHP native application.
