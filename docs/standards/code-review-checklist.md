# Engineering Code Review & Pull Request Checklist
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-CRW-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Security Controls Matrix (`VMS-DOC-SEC-SCM-001`)  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. Overview & Quality Gate Enforcement

Every pull request and code modification submitted to the `development` branch must satisfy this 20-point Engineering Code Review Checklist prior to approval and merging.

---

## 2. 20-Point Engineering Review Checklist

### A. Security & Authorization (Mandatory Pass)
- [ ] **1. Strict Owner Scoping (IDOR Check):** Every query fetching, mutating, or archiving a vehicle, service, or schedule explicitly filters by `user_id = $_SESSION['user_id']`.
- [ ] **2. Role Verification:** Protected endpoints call `AuthHelper::requireRole('admin')` or `AuthHelper::requireRole('user')` server-side before processing.
- [ ] **3. Admin Privacy Boundary:** Administrator controllers contain **zero** queries accessing private owner vehicle service histories or invoice details.
- [ ] **4. CSRF Protection:** All state-changing `POST` action handlers validate `CsrfHelper::validateToken($_POST['csrf_token'])`.
- [ ] **5. Output Encoding (XSS Check):** Dynamic variables rendered in HTML are escaped via `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- [ ] **6. Parameterized SQL (SQLi Check):** 100% of SQL queries use PDO prepared statements with bound parameters (`bindValue()`); zero direct SQL string concatenation.
- [ ] **7. Last Administrator Safeguard:** Any administrative action modifying user roles or account status verifies that at least one other active admin exists before allowing demotion/deactivation.

### B. Business Logic & Data Integrity
- [ ] **8. Monotonic Odometer Advancement:** Service records and mileage logs validate `service_mileage >= current_mileage` before writing to the database.
- [ ] **9. Transactional Atomicity:** Multi-table writes (e.g., logging a service record + updating current mileage + writing a mileage log) execute inside `PDO::beginTransaction()` and `commit()`.
- [ ] **10. Dual-Criteria Calculation:** Maintenance schedule status uses the unified calculation engine (`MaintenanceCalculator::calculateStatus()`) rather than static database columns.
- [ ] **11. Non-Destructive Archival:** Vehicle deletions set `status = 'archived'`; service record voiding sets `status = 'Voided'` with a mandatory reason.

### C. Architecture & Code Quality
- [ ] **12. Single Responsibility (SRP):** Controllers orchestrate flow; business logic resides in Services; database queries reside in Repositories/Helpers; Views only render data.
- [ ] **13. Strict Typing:** New PHP files include `declare(strict_types=1);` and declare explicit parameter and return types on all methods.
- [ ] **14. Unified Naming Conventions:** PascalCase for classes, camelCase for methods/variables, snake_case for tables/columns, kebab-case for CSS/JS.
- [ ] **15. Design Token Adherence:** Views use established design tokens (`--color-brand-navy`, `--color-brand-accent-teal`) without arbitrary inline hex codes.

### D. Error Handling & Logging
- [ ] **16. Safe Error Responses:** Production errors suppress raw stack traces and display user-friendly copy accompanied by a unique `request_id`.
- [ ] **17. Zero Secret/PII Logging:** Operational logs never record plaintext passwords, password hashes, session IDs, or encryption keys.

### E. Automated Testing & Verification
- [ ] **18. Unit / Integration Test Coverage:** New features, bug fixes, or calculation changes include accompanying PHPUnit tests in `tests/`.
- [ ] **19. Zero Test Regressions:** The automated test runner (`php tests/run_tests.php`) passes 100% with zero failures.
- [ ] **20. Documentation Updates:** Relevant technical specifications, route maps, or user manuals are updated to reflect any behavioral changes.

