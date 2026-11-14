# Phase 6: Authentication Implementation Guide

## 1. Architectural Overview

The Vehicle Maintenance Service Tracking System (VMS) implements a defense-in-depth authentication architecture adhering to OWASP Application Security Verification Standard (ASVS) Level 2 standards. Authentication is managed through centralized security components operating in coordination with native PHP session persistence, PDO database operations, and role-based access control.

```
       [ Public Web Request ]
                 │
                 ▼
     [ EmailNormalizer ] (Trim, lowercase, RFC 5322 validate)
                 │
                 ▼
       [ RateLimiter ] (IP & normalized email sliding window check)
                 │
                 ▼
      [ Database Query ] (Fetch active user record by normalized email)
                 │
                 ▼
     [ PasswordHasher ] (Verify via password_verify, test needsRehash)
                 │
                 ▼
      [ Session Hardening ] (session_regenerate_id, auth_version, audit log)
```

## 2. Public Registration Architecture

1. **Role Restriction**: Public registration strictly registers accounts with the role `user` (`VEHICLE_OWNER`).
2. **Role Injection Defense**: Any user-supplied inputs such as `role`, `is_admin`, `admin`, or `privileges` are explicitly stripped and discarded prior to insertion.
3. **Email Normalization**: All user emails are normalized using `VMS\Security\EmailNormalizer`:
   - Leading/trailing whitespace trimmed.
   - Non-printable control characters removed.
   - Lowercased canonically.
   - Validated against RFC 5322 specifications.
4. **Security Versions**: New user accounts are initialized with `auth_version = 1` and `status = 'active'`.

## 3. Password Hashing and Policy

- **Hashing Algorithm**: `PASSWORD_BCRYPT` with cost factor 12.
- **Verification**: `password_verify($password, $hash)` ensures constant-time verification.
- **Dynamic Re-hashing**: On successful authentication, `PasswordHasher::needsRehash()` checks if password algorithm or cost settings have changed and transparently upgrades stored hashes.
- **Password Policy**:
  - Minimum length: 8 characters.
  - Required character classes: Upper and lower case letters, numbers, and special characters.

## 4. Generic Authentication Error Responses

To prevent user enumeration and timing attacks:
- Failed login requests always produce the generic error message:
  `"Invalid email address or password."`
- The system returns this identical message regardless of whether the email does not exist, the account is deactivated, or the password was incorrect.
