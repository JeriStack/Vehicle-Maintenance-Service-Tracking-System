<?php
declare(strict_types=1);

namespace VMS\Security;

/**
 * Class PasswordHasher
 *
 * Cryptographic credential hashing, verification, rehashing, and password policy validation.
 */
class PasswordHasher
{
    public const MIN_LENGTH = 8;
    public const MAX_LENGTH = 128;

    /**
     * Hash a password using the current default password hashing algorithm (Bcrypt / Argon2).
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a plaintext password against a stored cryptographic hash.
     */
    public static function verify(string $password, string $hash): bool
    {
        if ($password === '' || $hash === '') {
            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * Check if a stored hash needs to be rehashed to match updated algorithm standards or cost parameters.
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Validate password adherence to application security policy.
     *
     * @return array<string> List of validation error messages (empty if valid)
     */
    public static function validatePolicy(string $password, ?string $confirmation = null): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = sprintf("Password must be at least %d characters long.", self::MIN_LENGTH);
        }

        if (strlen($password) > self::MAX_LENGTH) {
            $errors[] = sprintf("Password cannot exceed %d characters.", self::MAX_LENGTH);
        }

        if ($confirmation !== null && $password !== $confirmation) {
            $errors[] = "Passwords do not match.";
        }

        return $errors;
    }
}
