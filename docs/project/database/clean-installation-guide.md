# Clean Installation Guide (Scenario A)
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-009  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 4 & Phase 5 Foundation Specifications  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Prerequisites

- PHP 8.0 or higher with PDO, PDO_MySQL, and mbstring extensions enabled.
- MySQL 8.0+ or MariaDB 10.4+.
- Composer package manager.

---

## 2. Step-by-Step Clean Installation Procedure

### Step 1: Clone Repository & Install Dependencies
```bash
git clone https://github.com/JeriStack/Vehicle-Maintenance-Service-Tracking-System.git
cd Vehicle-Maintenance-Service-Tracking-System
composer install
```

### Step 2: Configure Environment
Copy `.env.example` to `.env` and set your database connection parameters:
```bash
cp .env.example .env
```
Ensure `.env` contains:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vehicle_maintenance_db
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### Step 3: Create the Empty Database
Open MySQL/MariaDB client and create the database with UTF-8 encoding:
```sql
CREATE DATABASE vehicle_maintenance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 4: Run Ordered Schema Migrations
Execute the migration runner to apply all 13 versioned schema migrations in sequence:
```bash
php bin/console migrate
```
Verify migration execution:
```bash
php bin/console migrate:status
```
All 13 migrations must report `Status: Applied` under `Batch: 1`.

### Step 5: Seed Baseline Reference Data
Populate the global automotive maintenance catalog (8 standardized service items):
```bash
php bin/console seed
```

### Step 6: Verify Database Integrity & Health
Run the automated database integration suite:
```bash
php tests/run_tests.php --database
```
Check application connectivity:
```bash
php bin/console health
```
Both probes must report successful connectivity, all tables present, and zero errors.

