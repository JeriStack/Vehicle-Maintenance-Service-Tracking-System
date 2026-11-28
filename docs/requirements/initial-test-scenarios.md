# Initial Test Scenarios & Verification Matrix
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ITS-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Baseline Established  
**Last Updated:** 2026-11-05  

---

## 1. Test Strategy Overview

The VMS testing methodology enforces automated verification across three test levels:
1. **Unit Testing (Level 1):** Standalone validation, helper logic, cryptographic token verification, and calculation engines running in isolated memory via `composer test`.
2. **Integration Testing (Level 2):** Database transactions, foreign key cascades, soft-archival persistence, and CLI background task execution.
3. **End-to-End & Security Testing (Level 3):** User journey verification, role boundaries, CSRF defense, and IDOR protection.

---

## 2. Test Scenarios Matrix

| Test ID | Req ID | Scenario Title | Preconditions | Test Steps | Test Data | Expected Result | Level | Priority | Execution Status |
|---|---|---|---|---|---|---|---|---|---|
| **TC-AUTH-01** | AUTH-001 | Valid Registration | Guest on register form | 1. Fill valid data<br>2. Submit form | Name: "Juan Cruz", Email: "juan@test.com", Pass: "Secret123!" | Account created with role `'user'`; bcrypt hashed password. | Unit/E2E | P0 | Automated (`ValidationTest`) |
| **TC-AUTH-02** | AUTH-001 | Duplicate Normalized Email | Email `juan@test.com` exists | 1. Enter `JUAN@TEST.COM`<br>2. Submit | Same email in uppercase | Registration rejected; friendly error returned. | Unit/Int | P0 | Automated (`ValidationTest`) |
| **TC-AUTH-03** | AUTH-002 | Valid Login | Active account exists | 1. Submit email & pass<br>2. Verify session | Valid credentials | Session regenerated; redirects to `/dashboard/`. | Unit/E2E | P0 | Automated (`AuthHelperTest`) |
| **TC-AUTH-04** | AUTH-002 | Bad Credentials | Account exists | 1. Enter wrong password<br>2. Submit | Wrong password | Rejection error; zero user enumeration leak. | Unit/E2E | P0 | Automated |
| **TC-AUTH-05** | AUTH-007 | Role Escalation Tampering | Guest on register form | 1. Inject `role=admin` in POST<br>2. Submit | POST payload with role | Injected role discarded; user created with role `'user'`. | Security | P0 | Automated |
| **TC-AUTH-06** | AUTH-005 | Remember-Me Entropy | User logs in with remember me | 1. Check generated token<br>2. Validate structure | Split-token selector & validator | Selector 16-bytes, validator 32-bytes; rotated on use. | Unit | P0 | Automated (`RememberMeTest`) |
| **TC-AUTH-07** | AUTH-006 | Password Reset Expiry | Reset requested | 1. Check token expiry timestamp | 60-minute window | Valid within 60 mins; fails after 60 mins. | Unit | P0 | Automated (`PasswordResetTest`) |
| **TC-VEH-01** | VEH-001 | Add Valid Vehicle | Authenticated owner | 1. Enter valid car details<br>2. Submit form | Plate: "ABC-123", Make: "Toyota", Year: 2022 | Vehicle inserted with user's ID; plate normalized. | E2E | P0 | Automated |
| **TC-VEH-02** | VEH-005 | Cross-Owner IDOR Block | User A owns car 1, User B owns car 2 | 1. User A opens `/vehicles/edit.php?id=2` | Target ID = 2 | Request blocked with HTTP 403 / Permission Denied. | Security | P0 | Automated |
| **TC-VEH-03** | VEH-003 | Odometer Regression Rejection | Current odometer = 50,000 | 1. Edit car mileage to 45,000<br>2. Submit | New mileage: 45,000 km | Rejected: mileage cannot decrease below 50,000 km. | Unit/E2E | P0 | Automated |
| **TC-VEH-04** | VEH-004 | Vehicle Archival Soft-Delete | Vehicle with 3 service records | 1. Submit delete action<br>2. Check DB records | Vehicle ID target | Status becomes `'archived'`; services retained. | Int | P0 | Automated |
| **TC-SRV-01** | SRV-001 | Service Odometer Advancement | Car odometer = 45,000 | 1. Log service at 48,000 km<br>2. Check car mileage | Mileage: 48,000 km | Service logged; master vehicle odometer updated to 48,000. | Int | P0 | Automated |
| **TC-SRV-02** | SRV-001 | Reject Future Completed Date | Today = `2026-11-05` | 1. Input date `2026-11-25`<br>2. Submit | Future date | Rejected: completed date cannot be in the future. | Unit/E2E | P0 | Automated (`DateHelperTest`) |
| **TC-SRV-03** | SRV-007 | Cost Decimal Precision | Valid service form | 1. Input cost `₱2,450.50`<br>2. Inspect DB | Decimal value: 2450.50 | Stored as `DECIMAL(10,2)`; formatted with `₱`. | Unit | P0 | Automated (`FunctionsTest`) |
| **TC-MTN-01** | MTN-002 | Overdue Date Precedence | Due date in past, km in future | 1. Run calculator engine | Date: -5 days, Km: +2,000 km | Status returned: **`OVERDUE`**. | Unit | P0 | Automated (`MaintenanceCalculatorTest`) |
| **TC-MTN-02** | MTN-002 | Overdue Mileage Precedence | Due date in future, km exceeded | 1. Run calculator engine | Date: +20 days, Km: -200 km | Status returned: **`OVERDUE`**. | Unit | P0 | Automated (`MaintenanceCalculatorTest`) |
| **TC-MTN-03** | MTN-002 | Due Today Date Status | Target date = today | 1. Run calculator engine | Date: today | Status returned: **`DUE`**. | Unit | P0 | Automated (`MaintenanceCalculatorTest`) |
| **TC-MTN-04** | MTN-002 | Upcoming Status in Window | Target date in 7 days | 1. Run calculator engine | Date: +7 days, Lead: 14 days | Status returned: **`UPCOMING`**. | Unit | P0 | Automated (`MaintenanceCalculatorTest`) |
| **TC-NTF-01** | NTF-004 | Reminder Deduplication | Notification generated today | 1. Re-run `bin/process_reminders.php` | Deterministic dedupe key | Second execution skips duplicate notification. | Int | P0 | Automated |
| **TC-NTF-02** | NTF-005 | WebSocket Handshake Vector | Client initiates WS | 1. Send Sec-WebSocket-Key<br>2. Check Accept response | RFC 6455 GUID vector | Valid Sec-WebSocket-Accept hash returned. | Unit | P0 | Automated (`WebSocketFrameTest`) |
| **TC-ADM-01** | ADM-004 | Last-Admin Protection | Only 1 admin in system | 1. Demote admin to user | User ID of sole admin | Action rejected; cannot demote last administrator. | Unit/Int | P0 | Automated |
| **TC-ADM-02** | ADM-004 | Admin Self-Demotion Block | Logged in as Admin 1 | 1. Admin 1 demotes Admin 1 | Target ID = current user | Action rejected: cannot change your own role. | Unit/Int | P0 | Automated |
| **TC-ADM-03** | ADM-007 | Admin Privacy Boundary | Admin visits `/vehicles/view.php` | 1. Admin requests owner car | Target car owned by user | HTTP 403 Forbidden; private repair history blocked. | Security | P0 | Automated |
| **TC-SEC-01** | NFR-SEC-03 | XSS Remark Escaping | Input containing `<script>` | 1. Render in template | `<script>alert(1)</script>` | HTML escaped to `&lt;script&gt;`. | Unit | P0 | Automated (`ValidationTest`) |
| **TC-SEC-02** | NFR-SEC-02 | SQL Injection in Search | Input with `' OR '1'='1` | 1. Run search query | Malicious SQL string | Parameterized PDO treats string as literal value. | Security | P0 | Automated |

