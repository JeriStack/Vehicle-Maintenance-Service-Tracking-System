# Error Response Conventions & Exception Handling
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-STD-ERR-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Security Architecture (`VMS-DOC-SEC-TM-001`)  
**Status:** Approved Standard  
**Last Updated:** 2026-11-05  

---

## 1. Core Principles & Information Leakage Prevention

1. **Zero Information Leakage in Production:** Production environments must never leak database connection strings, credentials, SQL queries, internal server paths, or stack traces to end users (`ini_set('display_errors', '0')`).
2. **Deterministic HTTP Status Codes:** Every error response—whether HTML or JSON—must emit the semantically accurate HTTP status code.
3. **Correlation ID Tracking:** Unhandled server exceptions generate a unique alphanumeric request identifier (`request_id` via `bin2hex(random_bytes(8))`) displayed to the user for support reference while logging the full exception trace privately in `logs/error.log`.

---

## 2. HTTP Status Code Conventions for HTML Views

| HTTP Code | Error Condition | User-Facing Screen / Behavior | Flash / Response Pattern |
|---|---|---|---|
| **`400`** | Bad Request | Form missing required fields, malformed parameters | Inline validation banner (`alert-danger`) |
| **`401`** | Unauthenticated | Session expired, guest attempting protected route | Redirect to `/auth/login.php` with `?redirect={target_url}` |
| **`403`** | Forbidden | User role mismatch (e.g. owner visiting `/admin/`), IDOR attempt | Dedicated `error/403.php` page: "Access Denied: You lack permissions." |
| **`404`** | Not Found | Route does not exist, resource ID not found | Dedicated `error/404.php` page: "Page or Vehicle Not Found." |
| **`405`** | Method Not Allowed | `GET` request to a state-changing action handler | Terminates with HTTP 405: "Method Not Allowed. Use POST." |
| **`419`** | Page Expired (CSRF) | CSRF token missing or mismatch | Redirect back with error: "Your session has expired. Please refresh." |
| **`422`** | Validation Failure | Form constraint failed (e.g. duplicate VIN, lower odometer) | Re-render form with inputs preserved and `.invalid-feedback` text |
| **`429`** | Rate Limited | Exceeded 5 login attempts within 15 minutes | Lockout screen: "Too many attempts. Please wait 15 minutes." |
| **`500`** | Internal Server Error | Uncaught database exception or fatal error | Dedicated `error/500.php`: "System error encountered. Ref: `{request_id}`" |

---

## 3. Structured JSON API & AJAX Error Response Standard

When responding to AJAX requests (e.g., notification polls, real-time status updates, modal form submissions), endpoints must respond with the `Content-Type: application/json; charset=utf-8` header and conform to this standardized schema:

### 3.1 Validation Failure Response (`HTTP 422 Unprocessable Entity`)
```json
{
    "success": false,
    "status_code": 422,
    "message": "The submitted vehicle details failed validation.",
    "errors": {
        "plate_number": [
            "The license plate number 'ABC-1234' is already registered in your garage."
        ],
        "current_mileage": [
            "Odometer reading cannot be lower than the vehicle's last recorded reading (45,000 km)."
        ]
    },
    "request_id": "req_8f1b92a4c1"
}
```

### 3.2 Authorization Failure Response (`HTTP 403 Forbidden`)
```json
{
    "success": false,
    "status_code": 403,
    "message": "Access Denied: You do not own the requested vehicle resource.",
    "errors": {},
    "request_id": "req_2a98f410c5"
}
```

### 3.3 Server Error Response (`HTTP 500 Internal Server Error`)
```json
{
    "success": false,
    "status_code": 500,
    "message": "An unexpected system error occurred while recording the service transaction.",
    "errors": {},
    "request_id": "err_5b82c199ef"
}
```

---

## 4. Exception Hierarchy Baseline

```text
Throwable
 └── Exception
      └── VmsException (Base Application Exception)
           ├── ValidationException (HTTP 422)
           ├── AuthenticationException (HTTP 401)
           ├── AuthorizationException (HTTP 403)
           ├── ResourceNotFoundException (HTTP 404)
           ├── CsrfTokenMismatchException (HTTP 419)
           └── DatabaseTransactionException (HTTP 500)
```

