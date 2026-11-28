# Role-Based Access Control (RBAC) and Authorization Policy

## 1. Application Roles

The system recognizes two distinct human application roles:
- `admin` (`SYSTEM_ADMIN`): Full administrative oversight, user management, global maintenance catalog, audit trail inspection, and platform configuration.
- `user` (`VEHICLE_OWNER`): Self-service owner-scoped management of vehicles, mileage logs, service histories, maintenance schedules, and notification preferences.

## 2. Authorization Middleware

Access control is enforced declaratively and middleware-driven via:
- `VMS\Middleware\RequireAuthentication`:
  - Enforces active session, valid idle timeout, active account status, and matching `auth_version`.
- `VMS\Middleware\RequireRole`:
  - `RequireRole::admin()`: Restricts route to users with role `'admin'`.
  - `RequireRole::owner()`: Restricts route to users with role `'user'`.
  - Rejects unauthorized users with HTTP 403 Forbidden.

## 3. IDOR and Owner-Scoped Data Isolation

To prevent Insecure Direct Object References (IDOR):
- Client-supplied resource IDs (`vehicle_id`, `record_id`, `schedule_id`) are never trusted in isolation.
- `VMS\Security\OwnershipGuard`:
  - `assertVehicleOwnership($pdo, $vehicleId, $userId)`: Confirms vehicle belongs to authenticated owner.
  - `assertServiceRecordOwnership($pdo, $recordId, $userId)`: Joins vehicle relation to verify record ownership.
  - Throws `SecurityException` (HTTP 403) on unauthorized cross-tenant access attempts.
- All SQL update/delete queries include mandatory tenant-scoping clauses:
  ```sql
  UPDATE vehicles SET current_mileage = ? WHERE id = ? AND user_id = ?
  ```
