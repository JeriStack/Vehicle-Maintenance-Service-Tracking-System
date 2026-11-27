# Seed Data Specification & Reference Catalog
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-007  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 1 Requirements & Phase 2 Data Dictionary  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Production Reference Catalog Seed Data

The global maintenance service catalog is seeded idempotently via `Database\Seeders\MaintenanceTypeSeeder` executed through `php bin/console seed`. Seed records use stable, immutable alphanumeric `code` keys rather than auto-increment IDs to ensure portability across environments.

| Stable Code (`code`) | Service Name (`name`) | URL Slug (`slug`) | Default Interval (Days) | Default Interval (KM) | Description |
|---|---|---|---|---|---|
| `oil-change` | Oil & Filter Change | `oil-change` | 180 days (6 mos) | 5,000 km | Engine oil replacement and new oil filter installation |
| `tire-rotation` | Tire Rotation & Balance | `tire-rotation` | 180 days (6 mos) | 10,000 km | Rotate and balance all four tires to ensure even tread wear |
| `brake-check` | Brake Inspection & Service | `brake-check` | 365 days (1 yr) | 20,000 km | Inspect brake pads, rotors, drums, and brake fluid lines |
| `battery-replacement` | Battery Replacement & Test | `battery-replacement` | 730 days (2 yrs) | 20,000 km | Inspect battery terminals, charge voltage, and alternator output |
| `engine-service` | Engine Service & Tune-up | `engine-service` | 365 days (1 yr) | 15,000 km | Replace engine spark plugs, inspect filters, and diagnostic check |
| `transmission-service` | Transmission Fluid Service | `transmission-service` | 1095 days (3 yrs) | 60,000 km | Drain and refill transmission fluid, inspect gaskets and seals |
| `vehicle-inspection` | Comprehensive Vehicle Inspection | `vehicle-inspection` | 365 days (1 yr) | 20,000 km | Multi-point safety inspection covering chassis, suspension, lights, and steering |
| `other` | Other Maintenance | `other` | NULL | NULL | General vehicle repair, custom servicing, or miscellaneous bodywork |

---

## 2. Seeder Idempotency & Repeatability

The seeder utilizes an `INSERT ... ON DUPLICATE KEY UPDATE` strategy keyed on `code`:
- If an entry does not exist, it is inserted with its approved defaults.
- If an entry already exists, its procedural description and intervals are synchronized without modifying its auto-increment primary key or disrupting dependent service records.
- Running `php bin/console seed` repeatedly produces exactly 8 catalog items without duplicating rows or incrementing unnecessary IDs.

---

## 3. Strict Production & Test Data Isolation

- **Zero Test Accounts in Production Seeders:** Production reference seeders **never** insert demo user accounts, sample vehicles, or dummy credentials.
- **Controlled Administrator Provisioning:** Administrator accounts are provisioned exclusively through controlled CLI setup commands or initial environment setup scripts, never hardcoded into baseline seeders.
- **Automated Test Fixtures:** Automated tests construct isolated test fixtures within disposable test databases (`vms_disposable_test_db`), guaranteeing zero contamination of staging or production databases.

