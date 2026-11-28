# Cross-Site Request Forgery (CSRF) Protection

## 1. Protection Strategy

All state-changing HTTP requests (`POST`, `PUT`, `DELETE`, `PATCH`) require a cryptographically secure anti-forgery token.

## 2. Token Architecture

- **Entropy**: 32 cryptographically secure pseudo-random bytes (`random_bytes(32)`) hex-encoded into a 64-character token.
- **Storage**: Stored in `$_SESSION['_csrf_token']` with legacy synchronization to `$_SESSION['csrf_token']`.
- **Comparison**: Verified using constant-time string comparison (`hash_equals()`) to prevent timing side-channel attacks.

## 3. Implementation

### Form Rendering
```php
<?= \VMS\Core\Csrf::field(); ?>
<!-- Renders: <input type="hidden" name="csrf_token" value="..." /> -->
```

### Middleware Enforcement
`VMS\Middleware\ValidateCsrfToken`:
- Safe methods (`GET`, `HEAD`, `OPTIONS`) bypass verification.
- Unsafe methods extract token from `$_POST['csrf_token']` or `X-CSRF-Token` header.
- Rejects missing or invalid tokens with HTTP 419 Page Expired / Invalid CSRF Token.

### Token Rotation
- Rotated upon authentication state transitions (login, logout, privilege changes).
