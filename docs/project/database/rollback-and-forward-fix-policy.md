# Migration Rollback & Forward-Fix Recovery Policy
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DB-008  
**Phase:** 5 — Database Schema, Migrations, and Seed Data  
**Authority:** Approved Phase 4 & Phase 5 Architectural Standards  
**Status:** Approved Technical Deliverable  
**Last Updated:** November 2026  

---

## 1. Relational DDL Characteristics & Constraints

In MySQL and MariaDB, Data Definition Language (DDL) statements—such as `CREATE TABLE`, `ALTER TABLE`, `DROP TABLE`, and `RENAME TABLE`—trigger an **implicit commit**. This means that schema operations cannot be enclosed within an atomic rollback block (`ROLLBACK`).

Therefore, the VMS project establishes a formal two-tier migration recovery policy:
1. **Reversible Migrations:** Applied when a migration introduces an isolated table or optional index that can be safely un-done without destroying user records. Reversible migrations provide a corresponding `.down.sql` file and can be reverted using `php bin/console migrate:rollback`.
2. **Data-Sensitive & Irreversible Migrations:** Applied when a migration involves schema reconciliation, column alterations, or data transformations. These migrations **must** follow the documented Forward-Fix Strategy.

---

## 2. Forward-Fix Strategy

When a schema migration encounters an unforeseen error or requires alteration against a non-disposable database:
1. **Never Drop Existing Databases:** Indiscriminate destructive commands (`DROP DATABASE`, `TRUNCATE TABLE`, `DELETE FROM`) are strictly forbidden against development, staging, or production environments.
2. **Inspect Error Logs:** Review structured diagnostic logs in `storage/logs/error.log` to determine the precise SQL failure, statement, and code.
3. **Draft Forward Migration:** Create an incremental, forward-moving migration (`YYYYMMDDHHMMSS_{fix_description}.sql`) that corrects the specific discrepancy.
4. **Test in Disposable Database:** Validate the forward migration against a temporary test database before executing it against live project data.
5. **Verify Data Continuity:** Ensure that all primary keys, foreign key relationships, user credentials, and service history remain intact post-execution.

---

## 3. Backup and Pre-Migration Protocol

Before applying migrations against any non-disposable database:
1. **Automated Pre-Migration Dump:** Execute `mysqldump` to create a complete SQL snapshot in `storage/backups/`:
   ```bash
   mysqldump -u root -p vehicle_maintenance_db > storage/backups/vehicle_maintenance_db_backup_$(date +%Y%m%d%H%M%S).sql
   ```
2. **Verify Backup Size:** Confirm that the output `.sql` file contains expected schema definitions and table data.
3. **Execute Migrations:** Run `php bin/console migrate`.
4. **Verify Status:** Check `php bin/console migrate:status` to ensure all files display `[Applied]` with their assigned batch number.
