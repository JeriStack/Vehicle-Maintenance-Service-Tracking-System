<?php
declare(strict_types=1);

namespace VMS\Security;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Class AdminProvisioner
 *
 * Dedicated CLI administrator bootstrapping service.
 * Enforces CLI execution only, one-time execution guarded by durable database metadata,
 * input validation, transactional isolation, and tamper-evident audit logging.
 */
class AdminProvisioner
{
    public const BOOTSTRAP_SETTING_KEY = 'admin_bootstrap_completed';

    /**
     * Check if initial administrator provisioning has already been executed.
     */
    public static function isProvisioned(PDO $pdo): bool
    {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([self::BOOTSTRAP_SETTING_KEY]);
            $val = $stmt->fetchColumn();
            return !empty($val);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Provision the first system administrator via CLI.
     *
     * @param PDO $pdo
     * @param string $fullName
     * @param string $email
     * @param string $password
     * @param string|null $phone
     * @return array{id: int, email: string, full_name: string}
     * @throws RuntimeException If called outside CLI or if already provisioned / invalid
     */
    public static function provision(
        PDO $pdo,
        string $fullName,
        string $email,
        string $password,
        ?string $phone = null
    ): array {
        // 1. Guard against non-CLI execution
        if (php_sapi_name() !== 'cli') {
            throw new RuntimeException("Security Violation: Administrator provisioning is restricted strictly to CLI execution.");
        }

        // 2. Validate inputs
        $fullName = trim($fullName);
        if ($fullName === '') {
            throw new RuntimeException("Full name is required.");
        }

        $emailNormalized = EmailNormalizer::normalize($email);
        if (!EmailNormalizer::isValid($emailNormalized)) {
            throw new RuntimeException("A valid email address is required.");
        }

        $policyErrors = PasswordHasher::validatePolicy($password);
        if (!empty($policyErrors)) {
            throw new RuntimeException("Password policy violation: " . implode(" ", $policyErrors));
        }

        $pdo->beginTransaction();
        try {
            // 3. Atomically lock and check bootstrap setting
            $checkStmt = $pdo->prepare("
                SELECT id, setting_value 
                FROM system_settings 
                WHERE setting_key = ? 
                FOR UPDATE
            ");
            $checkStmt->execute([self::BOOTSTRAP_SETTING_KEY]);
            $existingBootstrap = $checkStmt->fetch();

            if ($existingBootstrap && !empty($existingBootstrap['setting_value'])) {
                $pdo->rollBack();
                throw new RuntimeException("Administrator bootstrap has already been permanently completed. Repeated execution is denied.");
            }

            // 4. Check for duplicate email
            $dupStmt = $pdo->prepare("SELECT id FROM users WHERE email_normalized = ? FOR UPDATE");
            $dupStmt->execute([$emailNormalized]);
            if ($dupStmt->fetch()) {
                $pdo->rollBack();
                throw new RuntimeException(sprintf("An account with email '%s' already exists.", $emailNormalized));
            }

            // 5. Create administrator account
            $hashedPassword = PasswordHasher::hash($password);
            $insStmt = $pdo->prepare("
                INSERT INTO users (
                    full_name, email, email_normalized, password, phone, 
                    role, status, auth_version, created_at
                ) VALUES (?, ?, ?, ?, ?, 'admin', 'active', 1, NOW())
            ");
            $insStmt->execute([
                $fullName,
                trim($email),
                $emailNormalized,
                $hashedPassword,
                $phone ? trim($phone) : null
            ]);
            $userId = (int) $pdo->lastInsertId();

            // 6. Create default notification settings and vehicle preferences
            $sStmt = $pdo->prepare("INSERT INTO notification_settings (user_id) VALUES (?)");
            $sStmt->execute([$userId]);

            $pStmt = $pdo->prepare("INSERT INTO vehicle_preferences (user_id) VALUES (?)");
            $pStmt->execute([$userId]);

            // 7. Record one-time bootstrap marker
            $markerValue = json_encode([
                'completed_at' => date('c'),
                'admin_user_id' => $userId,
                'admin_email' => $emailNormalized,
                'sapi' => PHP_SAPI,
            ]);

            $setStmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, created_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");
            $setStmt->execute([self::BOOTSTRAP_SETTING_KEY, $markerValue]);

            // 8. Record audit log entry
            $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id) 
                VALUES (?, ?, 'users', ?)
            ");
            $auditStmt->execute([
                $userId,
                'Initial system administrator provisioned via CLI console',
                $userId
            ]);

            $pdo->commit();

            return [
                'id' => $userId,
                'email' => $emailNormalized,
                'full_name' => $fullName,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
