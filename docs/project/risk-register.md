# Risk Register & Management Plan
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-RR-001  
**Phase:** 0 — Project Initiation and Feasibility  
**Status:** Active & Monitored  
**Last Updated:** 2026-11-05  

---

## 1. Risk Evaluation Methodology

Risks are assessed using a standard $3 \times 3$ qualitative risk scoring matrix:

$$\text{Risk Score} = \text{Probability} \times \text{Impact}$$

* **Probability Scale:** Low (1), Medium (2), High (3)
* **Impact Scale:** Low (1), Medium (2), High (3)
* **Risk Rating Tiers:**
  * **Low (1–2):** Acceptable risk; monitor periodically.
  * **Medium (3–4):** Moderate risk; active mitigation required.
  * **High (6–9):** Critical risk; immediate architectural controls and automated verification gates required.

---

## 2. Risk Register Matrix

| Risk ID | Risk Description | Prob. (1-3) | Imp. (1-3) | Score (1-9) | Risk Tier | Primary Mitigation Strategy | Contingency Plan | Owner | Status |
|---|---|---|---|---|---|---|---|---|---|
| **R-001** | **Implementation vs. Blueprint Discrepancy:** Code deviates from the 32-page System Plan and UML blueprints (e.g. hard delete vs archive). | 2 | 3 | **6** | **High** | Conduct comprehensive Phase 0 audit; establish implementation gap register; review each PR against the blueprint. | Refactor divergent modules (e.g. adjust `actions/vehicle_action.php` to soft-archive). | Lead Architect | Active |
| **R-002** | **Environment & Version Incompatibility:** Differences between local developer environment (PHP 8.5/Windows) and CI runner (PHP 8.0/Ubuntu). | 2 | 2 | **4** | **Medium** | Test on multiple PHP versions (8.0, 8.1, 8.5) in GitHub Actions CI matrix; avoid language features exclusive to PHP 8.2+. | Restrict syntax to PHP 8.0 baseline syntax. | Dev Lead | Monitored |
| **R-003** | **Unauthorized Role Escalation & Self-Demotion:** Users elevating themselves to admin, or the last active admin demoting themselves, locking down administration. | 1 | 3 | **3** | **Medium** | Server-side role validation in `actions/admin_user_action.php`; block self-demotion and last-administrator demotion. | Database CLI emergency script to promote an admin account if locked out. | Security Engineer | Mitigated |
| **R-004** | **Cross-User Data Access (IDOR):** Vehicle owner accessing or mutating another owner's vehicles, services, or invoices by altering the query parameter ID. | 2 | 3 | **6** | **High** | Mandatory ownership check on every query (`WHERE id = ? AND user_id = ?`); enforce centralized RBAC guards on all action endpoints. | Reject request with HTTP 403 Forbidden; log unauthorized access attempts in `audit_logs`. | Security Engineer | Mitigated |
| **R-005** | **Incorrect Maintenance Calculations:** Faulty date or mileage boundary conditions leading to missed or false overdue alerts. | 2 | 3 | **6** | **High** | Pure, isolated calculation engine (`helpers/maintenance_calculator.php`) accompanied by comprehensive unit test suite (`MaintenanceCalculatorTest`). | Re-run calculation suite against historical test vectors; provide manual refresh trigger. | QA Lead | Mitigated |
| **R-006** | **Duplicate Notification Spamming:** Cron jobs or page reloads generating multiple identical alerts for the same service or registration event. | 2 | 2 | **4** | **Medium** | Enforce unique deduplication keys (`dedupe_key`) in database schema; apply 7-day suppression window in `NotificationHelper`. | Run deduplication cleanup query on `notifications` table. | Backend Dev | Mitigated |
| **R-007** | **WebSocket Exposure & Connection Starvation:** Unauthenticated clients connecting to port 8080 or exhausting server stream handles. | 2 | 2 | **4** | **Medium** | Authenticate incoming WebSocket handshakes via cookie session IDs; limit maximum concurrent connections per IP; non-blocking I/O. | Fall back seamlessly to database queue (`realtime_events`) and client-side AJAX polling. | Backend Dev | Mitigated |
| **R-008** | **Document Upload Vulnerabilities:** Malicious file uploads (e.g. `.php` masquerading as `.pdf` in vehicle documents). | 1 | 3 | **3** | **Medium** | Strict file extension allowlists (PDF, PNG, JPG only); MIME type inspection using `finfo`; store uploads outside public web root or disable execution via `.htaccess`. | Delete unrecognized files immediately; isolate upload folder permissions. | Security Engineer | Planned for Phase 4 |
| **R-009** | **Database Data Loss & Inconsistent Migrations:** Accidental database loss during development or schema divergence across team members. | 1 | 3 | **3** | **Medium** | Incremental SQL migration scripts in `database/migrations/`; preserve `database/seed.sql`; document daily backup commands in `SETUP_NOTES.txt`. | Restore latest daily SQL dump using `mysql -u root vehicle_maintenance_db < backup.sql`. | DevOps Lead | Monitored |
| **R-010** | **Dependency Rot & Uncontrolled Package Upgrades:** External Composer dependencies breaking native PHP functionality or introducing deprecation warnings. | 1 | 2 | **2** | **Low** | Zero runtime Composer dependencies; pin development dependencies in `composer.json` and commit `composer.lock`. | Revert to native standalone test runner (`tests/run_tests.php`) which requires zero Composer packages. | Lead Architect | Mitigated |

---

## 3. High-Priority Risk Mitigation Focus for Next Phase

1. **Risk R-001 (Blueprint Alignment):** In Phase 1, transition vehicle deletion from `DELETE FROM vehicles` to `UPDATE vehicles SET status = 'archived'` to guarantee that historical cost reports and maintenance logs are never corrupted.
2. **Risk R-004 (IDOR Protection):** Ensure all newly added routes verify `$_SESSION['user_id']` against vehicle records prior to displaying or updating data.
3. **Risk R-006 (Notification Deduplication):** Run migration `database/migrations/align_with_blueprint_schema.sql` to add the `dedupe_key` column to `notifications` across all environments.

