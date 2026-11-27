# Testing Standards & Quality Assurance Guidelines
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-TST-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** PHPUnit 9.5+, Automated Test Suite (`tests/run_tests.php`)  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. Test Suite Architecture & Existing Runner

The VMS application currently possesses a zero-dependency CLI test runner at [`tests/run_tests.php`](file:///c:/xampp/htdocs/GitHub_Clone_File_Projects/Vehicle-Maintenance-Service-Tracking-System/tests/run_tests.php) and PHPUnit configuration at [`phpunit.xml`](file:///c:/xampp/htdocs/GitHub_Clone_File_Projects/Vehicle-Maintenance-Service-Tracking-System/phpunit.xml).

The existing unit test suite verifies **44 tests across 156 assertions with 100% pass rate** in under **80 ms**.

---

## 2. Target Test Categorization Hierarchy

```text
tests/
├── Unit/                    # Pure, isolated unit tests (no network, no live database)
│   ├── AuthHelperTest.php
│   ├── DateHelperTest.php
│   ├── MaintenanceCalculatorTest.php
│   ├── RememberMeTest.php
│   ├── ValidationTest.php
│   └── WebSocketFrameTest.php
│
├── Integration/             # Database and service interaction tests (isolated test DB)
│   ├── VehicleRepositoryTest.php
│   ├── ServiceRecordTransactionTest.php
│   └── MileageLogProgressionTest.php
│
├── Feature/                 # End-to-end user journey workflows
│   ├── UserRegistrationJourneyTest.php
│   ├── VehicleGarageManagementTest.php
│   └── MaintenanceAlertWorkflowTest.php
│
├── Security/                # Penetration and security guard tests
│   ├── AuthorizationIdorTest.php
│   ├── CsrfValidationTest.php
│   ├── AdminPrivacyBoundaryTest.php
│   └── LastAdminProtectionTest.php
│
└── bootstrap.php            # Test environment configuration and autoloader setup
```

---

## 3. Test Class & Method Naming Conventions

1. **Test Class Naming:** Must mirror the tested class name with the `Test` suffix:
   - Target: `MaintenanceCalculator` -> Test Class: `MaintenanceCalculatorTest`.
   - File Path: `tests/Unit/MaintenanceCalculatorTest.php`.
2. **Test Method Naming Pattern:**
   Every test method must clearly declare the method being tested, the input scenario/condition, and the expected outcome:
   ```text
   test{Method}_{Condition}_{ExpectedOutcome}()
   ```
   *Examples:*
   - `testCalculateStatus_DateExceeded_ReturnsOverdue()`
   - `testUpdateOdometer_LowerReadingSubmitted_ThrowsValidationException()`
   - `testDemoteAdmin_SoleAdminAccount_AbortsWithProtectionError()`

---

## 4. Test Data Strategy & Database Isolation

1. **Transactional Rollback:** Integration tests executing database queries must wrap operations in a database transaction (`$db->beginTransaction()`) and invoke `$db->rollBack()` during test teardown (`tearDown()`), ensuring that no residual test records persist in the development database.
2. **Dedicated Test Database:** For CI/CD automated runs, tests execute against an isolated test database (`vms_test`) initialized via `database/schema.sql`.
3. **No Network Access in Unit Tests:** Unit tests must never initiate real external HTTP or SMTP connections. External mailers must operate in log mode (`MAIL_MAILER = 'log'`), and WebSocket sockets must be mocked or tested via frame encoder/decoder vector tests (`WebSocketFrameTest.php`).

---

## 5. Coverage Requirements & Exit Quality Gate

| Layer / Component | Minimum Line Coverage Target | Critical Verification Points |
|---|---|---|
| **Domain Calculations** | `>= 95%` | Dual-criteria maintenance calculator, date difference engines |
| **Security & Auth Guards** | `100%` | RBAC middleware, IDOR owner verification, last-admin safeguards |
| **Input Validation** | `>= 90%` | Plate regex, VIN length, monotonic odometer rules |
| **Transactional Actions**| `>= 85%` | Service recording with odometer update, vehicle archival |

