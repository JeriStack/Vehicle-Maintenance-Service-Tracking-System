<?php
declare(strict_types=1);

namespace VMS\Security;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Class AdminSecurity
 *
 * Enforces administrative operation security: administrator re-authentication,
 * transaction-safe last active administrator protection with row locking,
 * account status toggling, and immediate session invalidation.
 */
class AdminSecurity
{
    /**
     * Verify administrator credentials for sensitive state-changing operations.
     */
    public static function verifyAdminReauth(PDO $pdo, int $adminId, string $currentPassword): bool
    {
        if ($currentPassword === '' || $adminId <= 0) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("SELECT id, password, role, status FROM users WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();

            if (!$admin || $admin['role'] !== 'admin' || $admin['status'] !== 'active') {
                return false;
            }

            return PasswordHasher::verify($currentPassword, (string) $admin['password']);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Determines whether an administrator can be safely demoted or deactivated without leaving
     * the system without any active administrators. Uses transaction-level row locking (FOR UPDATE).
     */
    public static function canDemoteOrDeactivateAdmin(PDO $pdo, int $targetAdminId): bool
    {
        $startedTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            // Lock all active administrator rows to prevent race condition self-demotions
            $stmt = $pdo->prepare("
                SELECT id FROM users 
                WHERE role = 'admin' AND status = 'active' 
                FOR UPDATE
            ");
            $stmt->execute();
            $activeAdminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $activeAdminCount = count($activeAdminIds);

            // If the target is an active admin and the total active admins <= 1, prohibit operation
            $isTargetActiveAdmin = in_array($targetAdminId, array_map('intval', $activeAdminIds), true);

            $canProceed = !($isTargetActiveAdmin && $activeAdminCount <= 1);

            if ($startedTransaction) {
                // If we started this transaction solely for checking, commit it
                $pdo->commit();
            }

            return $canProceed;
        } catch (Throwable $e) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException("Failed checking last administrator status: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Invalidate all active sessions for a user by incrementing their auth_version.
     */
    public static function invalidateUserSessions(PDO $pdo, int $userId): void
    {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET auth_version = auth_version + 1 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Change user role with administrative re-authentication, last-admin protection, and audit logging.
     *
     * @return array{success: bool, message: string}
     */
    public static function changeUserRole(
        PDO $pdo,
        int $adminId,
        int $targetUserId,
        string $newRole,
        string $adminPassword
    ): array {
        if (!in_array($newRole, ['user', 'admin'], true)) {
            return ['success' => false, 'message' => "Invalid role selected."];
        }

        if ($adminId === $targetUserId && $newRole !== 'admin') {
            return ['success' => false, 'message' => "You cannot demote your own administrator privileges."];
        }

        if (!self::verifyAdminReauth($pdo, $adminId, $adminPassword)) {
            return ['success' => false, 'message' => "Administrator password verification failed."];
        }

        $pdo->beginTransaction();
        try {
            // Lock target row
            $tStmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ? FOR UPDATE");
            $tStmt->execute([$targetUserId]);
            $targetUser = $tStmt->fetch();

            if (!$targetUser) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Target user not found."];
            }

            $oldRole = (string) $targetUser['role'];

            // Demoting an admin requires last-admin verification
            if ($oldRole === 'admin' && $newRole === 'user') {
                if (!self::canDemoteOrDeactivateAdmin($pdo, $targetUserId)) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => "Operation rejected: Cannot demote the last active system administrator."];
                }
            }

            if ($oldRole !== $newRole) {
                $uStmt = $pdo->prepare("
                    UPDATE users 
                    SET role = ?, auth_version = auth_version + 1 
                    WHERE id = ?
                ");
                $uStmt->execute([$newRole, $targetUserId]);

                $logMsg = sprintf("Changed role from '%s' to '%s'", $oldRole, $newRole);
                $lStmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id) 
                    VALUES (?, ?, 'users', ?)
                ");
                $lStmt->execute([$adminId, $logMsg, $targetUserId]);
            }

            $pdo->commit();
            return ['success' => true, 'message' => "User role updated successfully."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Activate or deactivate user account with re-authentication, last-admin protection, and session invalidation.
     *
     * @return array{success: bool, message: string}
     */
    public static function toggleUserStatus(
        PDO $pdo,
        int $adminId,
        int $targetUserId,
        string $newStatus,
        string $adminPassword
    ): array {
        if (!in_array($newStatus, ['active', 'inactive'], true)) {
            return ['success' => false, 'message' => "Invalid status selected."];
        }

        if ($adminId === $targetUserId && $newStatus === 'inactive') {
            return ['success' => false, 'message' => "You cannot deactivate your own administrator account."];
        }

        if (!self::verifyAdminReauth($pdo, $adminId, $adminPassword)) {
            return ['success' => false, 'message' => "Administrator password verification failed."];
        }

        $pdo->beginTransaction();
        try {
            $tStmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ? FOR UPDATE");
            $tStmt->execute([$targetUserId]);
            $targetUser = $tStmt->fetch();

            if (!$targetUser) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Target user not found."];
            }

            $oldStatus = (string) $targetUser['status'];
            $role = (string) $targetUser['role'];

            // Deactivating an active admin requires last-admin verification
            if ($role === 'admin' && $oldStatus === 'active' && $newStatus === 'inactive') {
                if (!self::canDemoteOrDeactivateAdmin($pdo, $targetUserId)) {
                    $pdo->rollBack();
                    return ['success' => false, 'message' => "Operation rejected: Cannot deactivate the last active system administrator."];
                }
            }

            if ($oldStatus !== $newStatus) {
                $uStmt = $pdo->prepare("
                    UPDATE users 
                    SET status = ?, auth_version = auth_version + 1 
                    WHERE id = ?
                ");
                $uStmt->execute([$newStatus, $targetUserId]);

                // Clear persistent remember tokens if deactivated
                if ($newStatus === 'inactive') {
                    $dStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                    $dStmt->execute([$targetUserId]);
                }

                $logMsg = sprintf("Changed account status from '%s' to '%s'", $oldStatus, $newStatus);
                $lStmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, table_name, record_id) 
                    VALUES (?, ?, 'users', ?)
                ");
                $lStmt->execute([$adminId, $logMsg, $targetUserId]);
            }

            $pdo->commit();
            return ['success' => true, 'message' => "User status updated successfully."];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => "Database error: " . $e->getMessage()];
        }
    }
}
