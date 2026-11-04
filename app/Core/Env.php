<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Class Env
 *
 * Lightweight, zero-dependency environment configuration parser.
 * Reads .env files and safely populates $_ENV and $_SERVER.
 */
class Env
{
    private static bool $loaded = false;

    /**
     * Load environment variables from a file into $_ENV and $_SERVER.
     *
     * @param string $path Path to .env file
     * @return bool True if loaded, false otherwise
     */
    public static function load(string $path): bool
    {
        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Convert string booleans and null
            $parsedValue = match (strtolower($value)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                'empty', '(empty)' => '',
                default => $value,
            };

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$value}");
            }
            $_ENV[$key] = $parsedValue;
            $_SERVER[$key] = $parsedValue;
        }

        self::$loaded = true;
        return true;
    }

    /**
     * Retrieve an environment variable with optional fallback default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            return match (strtolower($val)) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'null', '(null)' => null,
                'empty', '(empty)' => '',
                default => $val,
            };
        }

        return $default;
    }

    /**
     * Check if environment has been loaded.
     */
    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}
