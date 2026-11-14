<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Class Config
 *
 * Centralized repository for application configuration values.
 */
class Config
{
    private static array $items = [];
    private static bool $booted = false;

    /**
     * Bootstrap configuration items from environment and config files.
     */
    public static function boot(string $basePath): void
    {
        if (self::$booted) {
            return;
        }

        // 1. Ensure environment variables are loaded
        Env::load($basePath . '/.env');

        // 2. Define baseline application configuration
        self::$items = [
            'app' => [
                'name' => (string) Env::get('APP_NAME', 'Vehicle Maintenance Service Tracking System'),
                'env' => (string) Env::get('APP_ENV', 'production'),
                'debug' => (bool) Env::get('APP_DEBUG', false),
                'url' => (string) Env::get('APP_URL', 'http://localhost/Vehicle-Maintenance-Service-Tracking-System/public'),
                'timezone' => (string) Env::get('APP_TIMEZONE', 'Asia/Manila'),
                'base_path' => $basePath,
                'public_path' => $basePath . '/public',
                'storage_path' => $basePath . '/storage',
            ],
            'database' => [
                'connection' => (string) Env::get('DB_CONNECTION', 'mysql'),
                'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
                'port' => (int) Env::get('DB_PORT', 3306),
                'database' => (string) Env::get('DB_DATABASE', 'vehicle_maintenance_db'),
                'username' => (string) Env::get('DB_USERNAME', 'root'),
                'password' => (string) Env::get('DB_PASSWORD', ''),
                'charset' => (string) Env::get('DB_CHARSET', 'utf8mb4'),
            ],
            'session' => [
                'driver' => (string) Env::get('SESSION_DRIVER', 'file'),
                'lifetime' => (int) Env::get('SESSION_LIFETIME', 120),
                'secure' => (bool) Env::get('SESSION_SECURE', false),
                'cookie' => 'vms_session',
                'path' => '/',
                'same_site' => 'Lax',
            ],
            'logging' => [
                'level' => (string) Env::get('LOG_LEVEL', 'debug'),
                'path' => (string) Env::get('LOG_PATH', 'storage/logs/app.log'),
            ],
            'websocket' => [
                'enabled' => (bool) Env::get('WEBSOCKET_ENABLED', true),
                'host' => (string) Env::get('WEBSOCKET_HOST', '127.0.0.1'),
                'port' => (int) Env::get('WEBSOCKET_PORT', 8080),
            ],
            'mail' => [
                'mailer' => (string) Env::get('MAIL_MAILER', 'log'),
                'host' => (string) Env::get('MAIL_HOST', '127.0.0.1'),
                'port' => (int) Env::get('MAIL_PORT', 1025),
                'username' => (string) Env::get('MAIL_USERNAME', ''),
                'password' => (string) Env::get('MAIL_PASSWORD', ''),
                'from_address' => (string) Env::get('MAIL_FROM_ADDRESS', 'noreply@vms.local'),
                'from_name' => (string) Env::get('MAIL_FROM_NAME', 'VMS Tracker'),
            ],
        ];

        // Set system timezone
        date_default_timezone_set(self::$items['app']['timezone']);

        self::$booted = true;
    }

    /**
     * Retrieve a configuration item using dot notation (e.g. 'database.host').
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Set a configuration item at runtime.
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target = $value;
    }

    /**
     * Return all configuration items.
     */
    public static function all(): array
    {
        return self::$items;
    }
}
