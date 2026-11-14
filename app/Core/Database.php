<?php
declare(strict_types=1);

namespace VMS\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Class Database
 *
 * Centralized PDO connection manager and query execution provider.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Get the active PDO instance, establishing a connection if necessary.
     *
     * @return PDO
     * @throws RuntimeException If connection cannot be established
     */
    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = (string) Config::get('database.host', '127.0.0.1');
        $port = (int) Config::get('database.port', 3306);
        $dbname = (string) Config::get('database.database', 'vehicle_maintenance_db');
        $charset = (string) Config::get('database.charset', 'utf8mb4');
        $user = (string) Config::get('database.username', 'root');
        $pass = (string) Config::get('database.password', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            self::$connection = new PDO($dsn, $user, $pass, $options);
            return self::$connection;
        } catch (PDOException $e) {
            // Log securely without leaking passwords
            $logPath = (string) Config::get('app.base_path') . '/storage/logs/error.log';
            $sanitizedMessage = sprintf(
                "[%s] Database connection failed for host '%s:%d', database '%s': %s\n",
                date('c'),
                $host,
                $port,
                $dbname,
                $e->getMessage()
            );

            if (is_dir(dirname($logPath)) && is_writable(dirname($logPath))) {
                error_log($sanitizedMessage, 3, $logPath);
            }

            throw new RuntimeException("Database service is currently unavailable. Please verify configuration.", (int) $e->getCode(), $e);
        }
    }

    /**
     * Test database connectivity without throwing unhandled exceptions.
     *
     * @return bool True if connection succeeds, false otherwise
     */
    public static function testConnection(): bool
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Reset the active connection (useful in long-running CLI or test runners).
     */
    public static function resetConnection(): void
    {
        self::$connection = null;
    }

    /**
     * Set a custom PDO instance (useful for unit/integration testing mocks).
     */
    public static function setConnection(?PDO $pdo): void
    {
        self::$connection = $pdo;
    }
}
