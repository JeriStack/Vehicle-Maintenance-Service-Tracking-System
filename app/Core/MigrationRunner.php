<?php
declare(strict_types=1);

namespace VMS\Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Class MigrationRunner
 *
 * Discovers, tracks, and executes incremental database schema migrations.
 */
class MigrationRunner
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(?PDO $db = null, ?string $migrationsPath = null)
    {
        $this->db = $db ?? Database::getConnection();
        $basePath = (string) Config::get('app.base_path', dirname(__DIR__, 2));
        $this->migrationsPath = $migrationsPath ?? $basePath . '/database/migrations';

        $this->ensureMigrationsTable();
    }

    /**
     * Ensure the migrations tracking table exists.
     */
    private function ensureMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL UNIQUE,
            `batch` INT UNSIGNED NOT NULL,
            `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->db->exec($sql);
    }

    /**
     * Retrieve all applied migration filenames from tracking table.
     */
    public function getAppliedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration, batch, executed_at FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve all migration files on disk.
     */
    public function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.sql');
        if ($files === false) {
            return [];
        }

        // Sort naturally by filename
        sort($files, SORT_NATURAL);

        $migrations = [];
        foreach ($files as $file) {
            $migrations[] = basename($file);
        }

        return $migrations;
    }

    /**
     * Get status of all migrations (both applied and pending).
     */
    public function getStatus(): array
    {
        $appliedMap = [];
        foreach ($this->getAppliedMigrations() as $row) {
            $appliedMap[$row['migration']] = $row;
        }

        $files = $this->getAllMigrationFiles();
        $status = [];

        foreach ($files as $file) {
            if (isset($appliedMap[$file])) {
                $status[] = [
                    'migration'   => $file,
                    'status'      => 'Applied',
                    'batch'       => $appliedMap[$file]['batch'],
                    'executed_at' => $appliedMap[$file]['executed_at'],
                ];
            } else {
                $status[] = [
                    'migration'   => $file,
                    'status'      => 'Pending',
                    'batch'       => null,
                    'executed_at' => null,
                ];
            }
        }

        return $status;
    }

    /**
     * Run all pending migrations in order.
     *
     * @return array List of executed migration filenames
     */
    public function migrate(): array
    {
        $statusList = $this->getStatus();
        $pending = array_filter($statusList, fn($s) => $s['status'] === 'Pending');

        if (empty($pending)) {
            return [];
        }

        // Determine current batch number
        $stmt = $this->db->query("SELECT COALESCE(MAX(batch), 0) + 1 AS next_batch FROM migrations");
        $nextBatch = (int) $stmt->fetchColumn();

        $executed = [];

        foreach ($pending as $item) {
            $filename = $item['migration'];
            $filePath = $this->migrationsPath . '/' . $filename;

            $sql = file_get_contents($filePath);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                // Execute SQL script
                $this->db->exec($sql);

                // Record in migrations table
                $ins = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
                $ins->execute([
                    ':migration' => $filename,
                    ':batch'     => $nextBatch,
                ]);

                $executed[] = $filename;
            } catch (Throwable $e) {
                throw new RuntimeException("Migration '{$filename}' failed: " . $e->getMessage(), 0, $e);
            }
        }

        return $executed;
    }
}
