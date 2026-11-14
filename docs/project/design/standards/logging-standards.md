# Logging Standards & Telemetry Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-LOG-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Security Architecture (`VMS-DOC-SEC-TM-001`)  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. Core Principles & Privacy Invariants

1. **Mandatory PII & Secret Redaction:** Under no circumstances may application, operational, or error logs capture:
   - Plaintext passwords or password reset tokens.
   - Bcrypt password hashes or remember-me validator strings.
   - Active session identifiers (`PHPSESSID`).
   - Database connection passwords or application secrets.
   - Credit card, payment, or private bank account details.
2. **Separation of Operational Logs vs. Audit Trails:**
   - *Operational Logs* (`logs/*.log`) are file-based, rotated logs used for troubleshooting, latency analysis, and daemon monitoring.
   - *Audit Trail Logs* (`audit_logs` database table) are immutable, non-repudiable business and security records tracking administrative and privileged account operations.

---

## 2. Severity Level Hierarchy (RFC 5424)

| Level | Severity Name | Operational Definition & Usage |
|---|---|---|
| **`DEBUG`** | 100 - Debug | Detailed diagnostic information during local development (disabled in production). |
| **`INFO`** | 200 - Informational | Routine successful milestones (e.g., user login, service record logged, vehicle archived). |
| **`NOTICE`** | 250 - Notice | Normal but significant conditions (e.g., password reset requested, schedule completed). |
| **`WARNING`** | 300 - Warning | Exceptional occurrences that are not errors (e.g., rate limit triggered, deprecated call). |
| **`ERROR`** | 400 - Error | Runtime errors that do not require immediate action but must be monitored (e.g., validation failure). |
| **`CRITICAL`** | 500 - Critical | Critical conditions requiring immediate intervention (e.g., database connection loss, daemon crash). |

---

## 3. Log Record Schema & Structured Formatting

All operational logs must be written as structured single-line JSON strings to facilitate parsing, grepping, and aggregation:

```json
{
    "timestamp": "2026-11-05T06:15:30.142Z",
    "severity": "INFO",
    "channel": "application",
    "event": "SERVICE_RECORD_CREATED",
    "request_id": "req_8f1b92a4c1",
    "actor_id": 14,
    "actor_role": "user",
    "module": "services",
    "route": "/actions/service_action.php",
    "ip_address": "192.168.1.100",
    "outcome": "SUCCESS",
    "context": {
        "vehicle_id": 5,
        "service_record_id": 142,
        "service_type_id": 1,
        "service_mileage": 45210,
        "cost": 65.00
    }
}
```

---

## 4. Log Channels & Dedicated Log Files

```text
logs/
├── app.log          # General application runtime, domain service dispatches, and business events
├── error.log        # Uncaught exceptions, PHP fatal errors, and database transaction failures
├── security.log     # Failed login attempts, CSRF mismatches, IDOR rejections, rate limiting
├── scheduler.log    # Headless CLI cron reminder executions, lock statuses, and reminder batches
└── websocket.log    # WebSocket server connection lifecycle, handshakes, frame errors, and disconnects
```

---

## 5. Log Rotation & File Security

1. **Rotation Policy:** Log files rotate daily (`app-YYYY-MM-DD.log`) or upon reaching `10 MB` in file size, retaining a maximum of 30 days of historical logs.
2. **Access Control:** The entire `logs/` directory is guarded by an Apache `.htaccess` rule (`Require all denied`). Direct HTTP retrieval of log files yields `HTTP 403 Forbidden`.

