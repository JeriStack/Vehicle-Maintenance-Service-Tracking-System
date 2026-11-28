<?php
declare(strict_types=1);

namespace VMS\Security;

use PDO;
use RuntimeException;

/**
 * Class OwnershipGuard
 *
 * Enforces owner-scoped resource authorization across all user-facing domain entities.
 * Ensures the owner identity originates exclusively from trusted server-side authentication context
 * and strictly prevents Insecure Direct Object Reference (IDOR) attacks.
 */
class OwnershipGuard
{
    /**
     * Retrieve a vehicle record verifying ownership against the authenticated user ID.
     */
    public static function getOwnedVehicle(PDO $pdo, int $vehicleId, int $userId): ?array
    {
        if ($vehicleId <= 0 || $userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT * 
            FROM vehicles 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$vehicleId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Assert that the authenticated user owns the given vehicle, throwing an exception if unauthorized.
     *
     * @throws RuntimeException
     */
    public static function assertVehicleOwnership(PDO $pdo, int $vehicleId, int $userId): array
    {
        $vehicle = self::getOwnedVehicle($pdo, $vehicleId, $userId);
        if (!$vehicle) {
            throw new RuntimeException("Access Denied: You do not have permission to access or modify this vehicle.");
        }
        return $vehicle;
    }

    /**
     * Retrieve a service record verifying ownership through its associated vehicle.
     */
    public static function getOwnedServiceRecord(PDO $pdo, int $recordId, int $userId): ?array
    {
        if ($recordId <= 0 || $userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT sr.*, v.make, v.model, v.plate_number 
            FROM service_records sr 
            JOIN vehicles v ON sr.vehicle_id = v.id 
            WHERE sr.id = ? AND v.user_id = ?
        ");
        $stmt->execute([$recordId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Assert that the authenticated user owns the vehicle tied to the given service record.
     *
     * @throws RuntimeException
     */
    public static function assertServiceRecordOwnership(PDO $pdo, int $recordId, int $userId): array
    {
        $record = self::getOwnedServiceRecord($pdo, $recordId, $userId);
        if (!$record) {
            throw new RuntimeException("Access Denied: You do not have permission to access or modify this service record.");
        }
        return $record;
    }

    /**
     * Retrieve a maintenance schedule verifying ownership through its associated vehicle.
     */
    public static function getOwnedMaintenanceSchedule(PDO $pdo, int $scheduleId, int $userId): ?array
    {
        if ($scheduleId <= 0 || $userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT ms.*, v.make, v.model, v.plate_number 
            FROM maintenance_schedules ms 
            JOIN vehicles v ON ms.vehicle_id = v.id 
            WHERE ms.id = ? AND v.user_id = ?
        ");
        $stmt->execute([$scheduleId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Retrieve a notification ensuring it belongs directly to the authenticated recipient user.
     */
    public static function getUserNotification(PDO $pdo, int $notificationId, int $userId): ?array
    {
        if ($notificationId <= 0 || $userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT * 
            FROM notifications 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Retrieve user notification preferences.
     */
    public static function getUserNotificationSettings(PDO $pdo, int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Retrieve user vehicle preferences.
     */
    public static function getUserVehiclePreferences(PDO $pdo, int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT * FROM vehicle_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
