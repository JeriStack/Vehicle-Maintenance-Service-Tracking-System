<?php
declare(strict_types=1);

namespace VMS\Security;

use PDO;
use Throwable;
use VMS\Core\Database;

/**
 * Class RateLimiter
 *
 * Database-backed failed login throttling defending against credential stuffing and brute-force attacks.
 * Evaluates both client IP address and normalized account identifier over a sliding time window.
 */
class RateLimiter
{
    public const MAX_ATTEMPTS = 5;
    public const WINDOW_SECONDS = 900; // 15 minutes

    /**
     * Determine if either the IP address or account identifier is currently throttled.
     */
    public static function isThrottled(string $ip, string $emailNormalized, ?PDO $pdo = null): bool
    {
        $attempts = self::getRecentAttemptCount($ip, $emailNormalized, $pdo);
        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Calculate remaining cooldown duration in seconds.
     */
    public static function getRemainingCooldown(string $ip, string $emailNormalized, ?PDO $pdo = null): int
    {
        $db = $pdo ?? self::getPdo();
        if ($db === null) {
            return 0;
        }

        try {
            $windowStart = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
            $stmt = $db->prepare("
                SELECT attempted_at 
                FROM login_attempts 
                WHERE (ip_address = ? OR email_normalized = ?) 
                  AND attempted_at >= ?
                ORDER BY attempted_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$ip, $emailNormalized, $windowStart]);
            $latest = $stmt->fetchColumn();

            if (!$latest) {
                return 0;
            }

            $elapsed = time() - strtotime((string) $latest);
            $remaining = self::WINDOW_SECONDS - $elapsed;
            return max(0, $remaining);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Record a failed login attempt for the given IP address and normalized email.
     */
    public static function recordFailedAttempt(string $ip, string $emailNormalized, ?PDO $pdo = null): void
    {
        $db = $pdo ?? self::getPdo();
        if ($db === null) {
            return;
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO login_attempts (ip_address, email_normalized, attempted_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$ip, $emailNormalized]);
        } catch (Throwable) {
            // Non-blocking fallback
        }
    }

    /**
     * Reset recorded failed attempts upon successful authentication.
     */
    public static function resetAttempts(string $ip, string $emailNormalized, ?PDO $pdo = null): void
    {
        $db = $pdo ?? self::getPdo();
        if ($db === null) {
            return;
        }

        try {
            $stmt = $db->prepare("
                DELETE FROM login_attempts 
                WHERE ip_address = ? OR email_normalized = ?
            ");
            $stmt->execute([$ip, $emailNormalized]);
        } catch (Throwable) {
            // Non-blocking fallback
        }
    }

    /**
     * Count recent failed attempts for the given IP and email within the sliding window.
     */
    public static function getRecentAttemptCount(string $ip, string $emailNormalized, ?PDO $pdo = null): int
    {
        $db = $pdo ?? self::getPdo();
        if ($db === null) {
            return 0;
        }

        try {
            $windowStart = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM login_attempts 
                WHERE (ip_address = ? OR email_normalized = ?) 
                  AND attempted_at >= ?
            ");
            $stmt->execute([$ip, $emailNormalized, $windowStart]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Purge historical attempts older than retention threshold.
     */
    public static function purgeOldAttempts(int $seconds = 86400, ?PDO $pdo = null): int
    {
        $db = $pdo ?? self::getPdo();
        if ($db === null) {
            return 0;
        }

        try {
            $cutoff = date('Y-m-d H:i:s', time() - $seconds);
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
            $stmt->execute([$cutoff]);
            return $stmt->rowCount();
        } catch (Throwable) {
            return 0;
        }
    }

    private static function getPdo(): ?PDO
    {
        try {
            return Database::getConnection();
        } catch (Throwable) {
            return null;
        }
    }
}
