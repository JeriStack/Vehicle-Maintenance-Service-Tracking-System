<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Class Session
 *
 * Hardened session management and flash messaging service.
 */
class Session
{
    private const FLASH_KEY = '_vms_flash_messages';
    private static bool $started = false;

    /**
     * Start secure session with hardened cookie flags.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        if (!headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            $lifetime = (int) Config::get('session.lifetime', 120) * 60;
            $secure = (bool) Config::get('session.secure', false);
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $secure = true;
            }

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            $cookieName = (string) Config::get('session.cookie', 'vms_session');
            session_name($cookieName);
            @session_start();
        } elseif (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        self::$started = (session_status() === PHP_SESSION_ACTIVE || isset($_SESSION));
    }

    /**
     * Check if session is currently active.
     */
    public static function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE || (isset($_SESSION) && is_array($_SESSION));
    }

    /**
     * Retrieve a value from session storage.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Store a value in session storage.
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Determine if a session key exists.
     */
    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Remove a key from session storage.
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Regenerate the session ID upon authentication transition.
     */
    public static function regenerate(bool $deleteOldSession = true): bool
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            return session_regenerate_id($deleteOldSession);
        }
        return false;
    }

    /**
     * Set a flash message for the subsequent HTTP request.
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    /**
     * Retrieve a flash message and immediately consume it.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        if (!isset($_SESSION[self::FLASH_KEY][$key])) {
            return $default;
        }
        $val = $_SESSION[self::FLASH_KEY][$key];
        unset($_SESSION[self::FLASH_KEY][$key]);
        return $val;
    }

    /**
     * Check if a specific flash message exists.
     */
    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }

    /**
     * Invalidate and destroy the active session and its cookie.
     */
    /**
     * Check if the authenticated session has exceeded the idle timeout.
     */
    public static function checkIdleTimeout(int $timeoutSeconds = 1800): bool
    {
        self::start();
        $lastActivity = (int) self::get('last_activity', 0);
        if ($lastActivity > 0 && (time() - $lastActivity) > $timeoutSeconds) {
            return false;
        }
        return true;
    }

    /**
     * Record current timestamp as the latest authenticated session activity.
     */
    public static function recordActivity(): void
    {
        self::start();
        self::set('last_activity', time());
    }

    /**
     * Verify that the session auth_version matches the database security version.
     */
    public static function isAuthVersionValid(int $expectedVersion): bool
    {
        self::start();
        $current = (int) self::get('auth_version', 1);
        return $current === $expectedVersion;
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            if (php_sapi_name() !== 'cli') {
                @session_destroy();
            }
        }

        self::$started = (php_sapi_name() === 'cli');
    }
}
