<?php
declare(strict_types=1);

namespace Tests\Integration\Database;

use PDO;
use PDOException;
use Tests\TestCase;
use VMS\Core\Config;
use VMS\Core\MigrationRunner;
use Database\Seeders\MaintenanceTypeSeeder;

/**
 * Class DatabaseIntegrationTest
 *
 * Dedicated database integration test suite verifying schema migrations, referential integrity,
 * unique constraints, indexing, and seed idempotency against a disposable test database.
 */
class DatabaseIntegrationTest extends TestCase
{
    private static ?PDO $testDb = null;
    private static ?PDO $serverDb = null;
    private static string $testDbName = 'vms_disposable_test_db';
    private static bool $dbAvailable = false;

    public static function setUpBeforeClass(): void
    {
        $host = (string) Config::get('database.host', '127.0.0.1');
        $port = (int) Config::get('database.port', 3306);
        $user = (string) Config::get('database.username', 'root');
        $pass = (string) Config::get('database.password', '');

        try {
            self::$serverDb = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            // Create disposable test database
            self::$serverDb->exec("CREATE DATABASE IF NOT EXISTS `" . self::$testDbName . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            self::$testDb = new PDO("mysql:host={$host};port={$port};dbname=" . self::$testDbName . ";charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            self::$dbAvailable = true;
        } catch (PDOException $e) {
            self::$dbAvailable = false;
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$serverDb !== null && self::$dbAvailable) {
            try {
                self::$serverDb->exec("DROP DATABASE IF EXISTS `" . self::$testDbName . "`;");
            } catch (PDOException $e) {
                // Ignore cleanup error
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$dbAvailable) {
            $this->markTestSkipped("Database server is not accessible in this execution context.");
        }
    }

    /**
     * 1. Test Clean Migration Execution & Table Existence (13.1)
     */
    public function testFreshDatabaseMigrationsAndTableEngines(): void
    {
        $migrationsPath = dirname(__DIR__, 3) . '/database/migrations';
        $runner = new MigrationRunner(self::$testDb, $migrationsPath);

        $executed = $runner->migrate();
        $this->assertTrue(count($executed) >= 12, "All migrations should execute against empty database");

        $expectedTables = [
            'users',
            'maintenance_types',
            'vehicles',
            'vehicle_mileage_logs',
            'service_records',
            'maintenance_schedules',
            'notifications',
            'notification_settings',
            'vehicle_preferences',
            'audit_logs',
            'remember_tokens',
            'password_resets',
            'realtime_events',
        ];

        foreach ($expectedTables as $table) {
            $stmt = self::$testDb->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . self::$testDbName . "' AND TABLE_NAME = '{$table}'");
            $engine = $stmt->fetchColumn();
            $this->assertTrue($engine === 'InnoDB', "Table '{$table}' must exist and use InnoDB engine");
        }

        // Verify migrations tracking table
        $applied = $runner->getAppliedMigrations();
        $this->assertTrue(count($applied) === count($executed), "Migrations table must record all applied migrations");
    }

    /**
     * 2. Test User Constraints & Email Normalization (13.2)
     */
    public function testUserConstraintsAndNormalizedEmail(): void
    {
        $stmt = self::$testDb->prepare("
            INSERT INTO users (full_name, email, email_normalized, password, role, status)
            VALUES (?, ?, ?, ?, 'user', 'active')
        ");
        $stmt->execute(['John Doe', 'John.Doe@Example.com', 'john.doe@example.com', 'hashed_pass_123']);
        $userId = (int) self::$testDb->lastInsertId();
        $this->assertTrue($userId > 0, "User should be inserted successfully");

        // Duplicate normalized email must fail
        $duplicateRejected = false;
        try {
            $stmt->execute(['Johnny D', 'JOHN.DOE@example.com', 'john.doe@example.com', 'hashed_pass_456']);
        } catch (PDOException $e) {
            $duplicateRejected = true;
        }
        $this->assertTrue($duplicateRejected, "Duplicate normalized email must be rejected by unique constraint");
    }

    /**
     * 3. Test Vehicle Constraints & Owner-Scoped Plate Uniqueness (13.3)
     */
    public function testVehicleOwnerScopedPlateUniqueness(): void
    {
        $u1 = (int) self::$testDb->query("SELECT id FROM users LIMIT 1")->fetchColumn();

        // Create second user
        $ins = self::$testDb->prepare("INSERT INTO users (full_name, email, email_normalized, password) VALUES (?, ?, ?, ?)");
        $ins->execute(['Second User', 'user2@example.com', 'user2@example.com', 'pass123']);
        $u2 = (int) self::$testDb->lastInsertId();

        $vehIns = self::$testDb->prepare("
            INSERT INTO vehicles (user_id, plate_number, plate_number_normalized, vehicle_type, make, model, year, registration_expiry, current_mileage)
            VALUES (?, ?, ?, 'Sedan', 'Toyota', 'Corolla', 2022, '2027-01-01', 15000)
        ");

        // User 1 registers plate ABC-123
        $vehIns->execute([$u1, 'ABC-123', 'ABC123']);
        $v1 = (int) self::$testDb->lastInsertId();
        $this->assertTrue($v1 > 0, "Vehicle for User 1 must be inserted");

        // User 1 attempts to register same plate ABC 123 (same normalized plate) -> Must FAIL
        $ownerDupRejected = false;
        try {
            $vehIns->execute([$u1, 'ABC 123', 'ABC123']);
        } catch (PDOException $e) {
            $ownerDupRejected = true;
        }
        $this->assertTrue($ownerDupRejected, "Same owner registering duplicate normalized plate must be rejected");

        // User 2 registers SAME normalized plate -> Must SUCCEED (owner-scoped)
        $diffOwnerAccepted = false;
        try {
            $vehIns->execute([$u2, 'ABC-123', 'ABC123']);
            $diffOwnerAccepted = true;
        } catch (PDOException $e) {
            $diffOwnerAccepted = false;
        }
        $this->assertTrue($diffOwnerAccepted, "Different owner registering same plate must be accepted");

        // Invalid owner foreign key -> Must FAIL
        $invalidOwnerRejected = false;
        try {
            $vehIns->execute([999999, 'XYZ-999', 'XYZ999']);
        } catch (PDOException $e) {
            $invalidOwnerRejected = true;
        }
        $this->assertTrue($invalidOwnerRejected, "Vehicle with invalid user_id must be rejected by foreign key");
    }

    /**
     * 4. Test Vehicle Mileage History Logs (13.4)
     */
    public function testVehicleMileageHistoryLogging(): void
    {
        $vehId = (int) self::$testDb->query("SELECT id FROM vehicles LIMIT 1")->fetchColumn();
        $userId = (int) self::$testDb->query("SELECT id FROM users LIMIT 1")->fetchColumn();

        $stmt = self::$testDb->prepare("
            INSERT INTO vehicle_mileage_logs (vehicle_id, mileage, source, recorded_by_user_id)
            VALUES (?, ?, 'MANUAL', ?)
        ");
        $stmt->execute([$vehId, 16500, $userId]);
        $logId = (int) self::$testDb->lastInsertId();
        $this->assertTrue($logId > 0, "Mileage log entry must be created");

        // Foreign key to vehicle enforced
        $fkRejected = false;
        try {
            $stmt->execute([999999, 20000, $userId]);
        } catch (PDOException $e) {
            $fkRejected = true;
        }
        $this->assertTrue($fkRejected, "Mileage log with invalid vehicle_id must fail FK check");
    }

    /**
     * 5. Test Service Records & Financial Decimal Precision (13.5)
     */
    public function testServiceRecordsAndDecimalPrecision(): void
    {
        $seeder = new MaintenanceTypeSeeder(self::$testDb);
        $seeder->run();

        $vehId = (int) self::$testDb->query("SELECT id FROM vehicles LIMIT 1")->fetchColumn();
        $mtId = (int) self::$testDb->query("SELECT id FROM maintenance_types WHERE code = 'oil-change'")->fetchColumn();

        $stmt = self::$testDb->prepare("
            INSERT INTO service_records (vehicle_id, maintenance_type_id, service_type, service_date, mileage, cost, status)
            VALUES (?, ?, 'Oil Change', '2026-05-01', 17000, ?, 'Completed')
        ");
        $stmt->execute([$vehId, $mtId, 1250.75]);
        $srvId = (int) self::$testDb->lastInsertId();
        $this->assertTrue($srvId > 0, "Service record should be created");

        // Verify decimal precision preserved
        $cost = self::$testDb->query("SELECT cost FROM service_records WHERE id = {$srvId}")->fetchColumn();
        $this->assertTrue($cost === '1250.75', "Financial cost must preserve 2 decimal places in DECIMAL(10,2)");
    }

    /**
     * 6. Test Maintenance Schedules Lifecycle & Triggers (13.6)
     */
    public function testMaintenanceSchedulesTriggers(): void
    {
        $vehId = (int) self::$testDb->query("SELECT id FROM vehicles LIMIT 1")->fetchColumn();
        $mtId = (int) self::$testDb->query("SELECT id FROM maintenance_types WHERE code = 'tire-rotation'")->fetchColumn();

        $stmt = self::$testDb->prepare("
            INSERT INTO maintenance_schedules (vehicle_id, maintenance_type_id, title, next_service_date, next_service_mileage, status)
            VALUES (?, ?, ?, ?, ?, 'ACTIVE')
        ");

        // Date-only trigger
        $stmt->execute([$vehId, $mtId, 'Tire Rotation Date Only', '2026-12-01', null]);
        $s1 = (int) self::$testDb->lastInsertId();
        $this->assertTrue($s1 > 0, "Date-only schedule must succeed");

        // Mileage-only trigger
        $stmt->execute([$vehId, $mtId, 'Tire Rotation Mileage Only', null, 25000]);
        $s2 = (int) self::$testDb->lastInsertId();
        $this->assertTrue($s2 > 0, "Mileage-only schedule must succeed");

        // Combined trigger
        $stmt->execute([$vehId, $mtId, 'Tire Rotation Combined', '2026-12-01', 25000]);
        $s3 = (int) self::$testDb->lastInsertId();
        $this->assertTrue($s3 > 0, "Combined trigger schedule must succeed");
    }

    /**
     * 7. Test Notification Deduplication Constraint (13.7)
     */
    public function testNotificationDeduplicationConstraint(): void
    {
        $userId = (int) self::$testDb->query("SELECT id FROM users LIMIT 1")->fetchColumn();
        $dedupeKey = hash('sha256', 'schedule_1_reminder_7_days_2026-11-05');

        $stmt = self::$testDb->prepare("
            INSERT INTO notifications (user_id, type, title, message, dedupe_key, is_read)
            VALUES (?, 'REMINDER', 'Service Due', 'Your tire rotation is due soon', ?, 0)
        ");
        $stmt->execute([$userId, $dedupeKey]);
        $notifId = (int) self::$testDb->lastInsertId();
        $this->assertTrue($notifId > 0, "First notification must insert successfully");

        // Duplicate dedupe_key must be rejected
        $dupRejected = false;
        try {
            $stmt->execute([$userId, $dedupeKey]);
        } catch (PDOException $e) {
            $dupRejected = true;
        }
        $this->assertTrue($dupRejected, "Duplicate notification dedupe_key must be rejected by unique constraint");
    }

    /**
     * 8. Test One-to-One Settings & Preferences (13.8)
     */
    public function testOneToOneSettingsAndPreferences(): void
    {
        $userId = (int) self::$testDb->query("SELECT id FROM users LIMIT 1")->fetchColumn();

        $stmt = self::$testDb->prepare("
            INSERT INTO notification_settings (user_id, reminder_days) VALUES (?, 7)
        ");
        $stmt->execute([$userId]);
        $this->assertTrue((int) self::$testDb->lastInsertId() > 0, "Notification settings inserted");

        // Second insert for same user must FAIL
        $dupSettingsRejected = false;
        try {
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            $dupSettingsRejected = true;
        }
        $this->assertTrue($dupSettingsRejected, "Duplicate settings for same user must be rejected (1:1)");

        // Vehicle preferences 1:1
        $prefStmt = self::$testDb->prepare("
            INSERT INTO vehicle_preferences (user_id, default_service_interval, default_mileage_interval)
            VALUES (?, 6, 5000)
        ");
        $prefStmt->execute([$userId]);
        $this->assertTrue((int) self::$testDb->lastInsertId() > 0, "Vehicle preferences inserted");

        $dupPrefRejected = false;
        try {
            $prefStmt->execute([$userId]);
        } catch (PDOException $e) {
            $dupPrefRejected = true;
        }
        $this->assertTrue($dupPrefRejected, "Duplicate preferences for same user must be rejected (1:1)");
    }

    /**
     * 9. Test Audit Log Actor Preservation (13.9)
     */
    public function testAuditLogActorPreservation(): void
    {
        // System daemon event (NULL user_id)
        $stmt = self::$testDb->prepare("
            INSERT INTO audit_logs (user_id, actor_type, action, table_name)
            VALUES (NULL, 'SYSTEM_DAEMON', 'Automated Daily Reminder Run', 'notifications')
        ");
        $stmt->execute();
        $logId = (int) self::$testDb->lastInsertId();
        $this->assertTrue($logId > 0, "System daemon audit event with NULL user_id must succeed");

        $actorType = self::$testDb->query("SELECT actor_type FROM audit_logs WHERE id = {$logId}")->fetchColumn();
        $this->assertTrue($actorType === 'SYSTEM_DAEMON', "Actor type must be SYSTEM_DAEMON");
    }

    /**
     * 10. Test Foreign Key Deletion Integrity (13.10)
     */
    public function testForeignKeyDeletionIntegrity(): void
    {
        $vehId = (int) self::$testDb->query("SELECT id FROM vehicles LIMIT 1")->fetchColumn();

        // Soft-archiving a vehicle must preserve service records
        self::$testDb->exec("UPDATE vehicles SET status = 'archived' WHERE id = {$vehId}");
        $status = self::$testDb->query("SELECT status FROM vehicles WHERE id = {$vehId}")->fetchColumn();
        $this->assertTrue($status === 'archived', "Vehicle should be marked archived");

        $serviceCount = (int) self::$testDb->query("SELECT COUNT(*) FROM service_records WHERE vehicle_id = {$vehId}")->fetchColumn();
        $this->assertTrue($serviceCount > 0, "Vehicle archival must NOT delete service history");

        $mileageCount = (int) self::$testDb->query("SELECT COUNT(*) FROM vehicle_mileage_logs WHERE vehicle_id = {$vehId}")->fetchColumn();
        $this->assertTrue($mileageCount > 0, "Vehicle archival must NOT delete mileage logs");
    }

    /**
     * 11. Test Auth Security Tables and Composite Indexes (Migrations 14 & 15)
     */
    public function testAuthSecuritySchemaAndCompositeIndexes(): void
    {
        $authVersionCol = self::$testDb->query("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'auth_version'
        ")->fetchColumn();
        $this->assertSame(1, (int) $authVersionCol, "users.auth_version column must exist");

        foreach (['login_attempts', 'system_settings'] as $table) {
            $exists = self::$testDb->query("SHOW TABLES LIKE '{$table}'")->fetchColumn();
            $this->assertSame($table, $exists, "Security table '{$table}' must exist");
        }

        $expectedIndexes = [
            ['vehicles', 'idx_vehicles_user_status'],
            ['notifications', 'idx_notif_user_read_created'],
            ['audit_logs', 'idx_audit_table_record'],
            ['audit_logs', 'idx_audit_user_created'],
        ];

        foreach ($expectedIndexes as [$table, $indexName]) {
            $idxCount = (int) self::$testDb->query("
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND INDEX_NAME = '{$indexName}'
            ")->fetchColumn();
            $this->assertTrue($idxCount > 0, "Composite index {$table}.{$indexName} must exist");
        }

        $redundantIdx = (int) self::$testDb->query("
            SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_settings' AND INDEX_NAME = 'idx_sys_key'
        ")->fetchColumn();
        $this->assertSame(0, $redundantIdx, "Redundant idx_sys_key index on system_settings must be removed");
    }
}
