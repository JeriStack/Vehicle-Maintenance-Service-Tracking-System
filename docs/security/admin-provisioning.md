# One-Time CLI Administrator Provisioning

## 1. Overview

Administrative accounts cannot be created via public HTTP registration. To bootstrap the initial system administrator in fresh deployments, VMS provides a secure, one-time command-line interface provisioner.

## 2. CLI Command Specification

```bash
php bin/console admin:create --name="Root Administrator" --email="admin@example.com" --password="SuperSecurePassword123!"
```

## 3. Security Invariants

1. **CLI Execution Guard**:
   - `AdminProvisioner` verifies `php_sapi_name() === 'cli'`. Invocation from web requests throws an immediate fatal error.
2. **Durable One-Time Bootstrap Marker**:
   - Checks the `system_settings` table for `initial_admin_created`.
   - Once an administrator is provisioned via this mechanism, `initial_admin_created` is set to `'1'`, locking the bootstrap mechanism against reuse.
3. **Password Policy Enforcement**:
   - Enforces uppercase, lowercase, numeric, and symbol complexity with minimum 8 characters.
4. **Audit Trail**:
   - Logs administrative account creation with IP `CLI` and user agent `CLI Command Runner`.
