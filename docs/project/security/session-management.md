# Session Management Architecture and Lifecycle

## 1. Session Configuration and Cookie Hardening

VMS enforces hardened cookie parameters and session settings in `app/Core/Session.php`:

| Parameter | Configuration | Purpose |
|:---|:---|:---|
| `session.cookie_httponly` | `true` | Prevents JavaScript access (XSS mitigation) |
| `session.cookie_secure` | Auto (`true` on HTTPS) | Encrypts session cookies across network |
| `session.cookie_samesite` | `'Lax'` | Mitigates Cross-Site Request Forgery (CSRF) |
| `session.use_strict_mode` | `'1'` | Prevents uninitialized session ID fixation |
| `session.use_only_cookies` | `'1'` | Blocks session ID passing in URLs |
| `session.name` | `'vms_session'` | Hides default PHP session identifier name |

## 2. Session Lifecycle

```
[ Unauthenticated Session ]
          │
          │ Login Success
          ▼
[ Session Regeneration ] ─── session_regenerate_id(true)
          │
          ├── Store user_id, role, auth_version, email, last_activity
          │
          ▼
[ Active Authenticated Request ]
          │
          ├── 1. Check Idle Timeout (default 1800s / 30m)
          │      └── If exceeded: Session::destroy(), redirect to login
          │
          ├── 2. Validate auth_version against users.auth_version in DB
          │      └── If mismatch: Session::destroy(), force re-login
          │
          └── 3. Record Activity (updates last_activity)
```

## 3. Session Invalidation

- **Logout**: Controlled POST request validated by CSRF token clears `$_SESSION`, destroys the cookie, and calls `session_destroy()`.
- **Privilege / Status Changes**: Changing user roles or disabling accounts immediately increments `users.auth_version`. Subsequent requests from affected active sessions immediately fail validation and are destroyed.
