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
5. Access the application in your browser:
   `http://localhost/Vehicle-Maintenance-Service-Tracking-System/`

## Configuration

If you change the folder name, update the `BASE_URL` in `config/constants.php`:

```php
define('BASE_URL', '/Your-Folder-Name/');
```

Database credentials can be updated in `config/database.php`.

## Author
Developed as a complete Phase 1-38 implementation for a robust PHP native application.
