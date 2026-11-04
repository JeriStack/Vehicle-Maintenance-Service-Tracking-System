<?php
// bin/process_reminders.php
/**
 * Vehicle Maintenance Service Tracking System - CLI Scheduled Reminder Engine
 *
 * Implements Blueprint Section 14.3 and UML Sequence Diagram 08:
 * - CLI-only execution with process concurrency lock
 * - Evaluates maintenance schedules and vehicle registration expiry
 * - Respects per-owner notification preferences and lead times
 * - Generates idempotent notifications using stable deduplication keys
 * - Dispatches real-time alerts via WebSocket
 *
 * Usage:
 *   php bin/process_reminders.php
 * Executed daily via Windows Task Scheduler or Linux Cron.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden: This script can only be executed via CLI.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/maintenance_calculator.php';
require_once __DIR__ . '/../helpers/websocket_helper.php';

$lockFile = sys_get_temp_dir() . '/vms_reminders.lock';
$lockHandle = fopen($lockFile, 'c+');

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Process already running. Exiting.\n";
    exit(0);
}

echo "====================================================================\n";
echo " VMS Automated Reminder Scheduler\n";
echo " Started at: " . date('Y-m-d H:i:s') . "\n";
echo " Timezone:   " . date_default_timezone_get() . "\n";
echo "====================================================================\n";

$notificationsCreated = 0;

try {
    $pdo = Database::getInstance();

    // Fetch all active vehicle owners
    $userStmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'user' OR role = 'vehicle_owner'");
    $users = $userStmt->fetchAll();

    foreach ($users as $user) {
        $userId = (int)$user['id'];

        // 1. Load Owner Notification Preferences
        $prefStmt = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
        $prefStmt->execute([$userId]);
        $prefs = $prefStmt->fetch();

        $maintenanceReminderDays = isset($prefs['reminder_days']) ? (int)$prefs['reminder_days'] : 7;
        $registrationReminderDays = 30; // Blueprint default
        $distanceWarningKm = 500;       // Blueprint default

        $checkMaintenance  = !isset($prefs['maintenance_reminder']) || $prefs['maintenance_reminder'];
        $checkRegistration = !isset($prefs['registration_reminder']) || $prefs['registration_reminder'];
        $checkDue          = !isset($prefs['service_due_reminder']) || $prefs['service_due_reminder'];
        $checkOverdue      = !isset($prefs['overdue_reminder']) || $prefs['overdue_reminder'];

        // 2. Evaluate Registration Expiry for Owner Vehicles
        if ($checkRegistration) {
            $vehStmt = $pdo->prepare("
                SELECT id, make, model, plate_number, registration_expiry 
                FROM vehicles 
                WHERE user_id = ? AND status = 'Active' AND registration_expiry IS NOT NULL
            ");
            $vehStmt->execute([$userId]);
            $vehicles = $vehStmt->fetchAll();

            foreach ($vehicles as $v) {
                $expiryDate = $v['registration_expiry'];
                $targetTime = strtotime($expiryDate);
                $daysDiff = (int)round(($targetTime - strtotime(date('Y-m-d'))) / 86400);

                if ($daysDiff < 0) {
                    // Registration Expired
                    $dedupeKey = "registration_expired_{$v['id']}_{$expiryDate}";
                    $title     = "Registration Expired";
                    $message   = sprintf("Vehicle %s %s (%s) registration expired on %s.", $v['make'], $v['model'], $v['plate_number'], $expiryDate);
                    $type      = "OVERDUE";

                    if (insertNotificationIfUnique($pdo, $userId, $v['id'], null, $type, $title, $message, $dedupeKey)) {
                        $notificationsCreated++;
                        WebSocketHelper::notifyUser($userId, $title, $message, $type, ['vehicle_id' => $v['id']]);
                    }
                } elseif ($daysDiff <= $registrationReminderDays) {
                    // Registration Expiring Soon
                    $dedupeKey = "registration_upcoming_{$v['id']}_{$expiryDate}_{$daysDiff}d";
                    $title     = "Registration Expiring Soon";
                    $message   = sprintf("Vehicle %s %s (%s) registration expires in %d day(s) on %s.", $v['make'], $v['model'], $v['plate_number'], $daysDiff, $expiryDate);
                    $type      = "REMINDER";

                    if (insertNotificationIfUnique($pdo, $userId, $v['id'], null, $type, $title, $message, $dedupeKey)) {
                        $notificationsCreated++;
                        WebSocketHelper::notifyUser($userId, $title, $message, $type, ['vehicle_id' => $v['id']]);
                    }
                }
            }
        }

        // 3. Evaluate Maintenance Schedules / Completed Service Next Projections
        if ($checkMaintenance) {
            // Check completed service records with next service targets
            $svcStmt = $pdo->prepare("
                SELECT sr.id as record_id, sr.service_type, sr.next_service_date, sr.next_service_mileage,
                       v.id as vehicle_id, v.make, v.model, v.plate_number, v.current_mileage
                FROM service_records sr
                JOIN vehicles v ON sr.vehicle_id = v.id
                WHERE v.user_id = ? AND v.status = 'Active' AND sr.status = 'Completed'
                  AND (sr.next_service_date IS NOT NULL OR sr.next_service_mileage IS NOT NULL)
            ");
            $svcStmt->execute([$userId]);
            $services = $svcStmt->fetchAll();

            foreach ($services as $s) {
                $eval = MaintenanceCalculator::evaluate(
                    $s['next_service_date'],
                    $s['next_service_mileage'],
                    $s['current_mileage'],
                    $maintenanceReminderDays,
                    $distanceWarningKm
                );

                $status = $eval['status'];

                if ($status === MaintenanceCalculator::STATUS_OVERDUE && $checkOverdue) {
                    $dedupeKey = "maint_overdue_svc_{$s['record_id']}_" . ($s['next_service_date'] ?: 'dist') . "_" . date('Y-m');
                    $title     = "Service Overdue";
                    $message   = sprintf("%s is overdue for %s %s (%s). %s.", $s['service_type'], $s['make'], $s['model'], $s['plate_number'], $eval['reason']);
                    $type      = "OVERDUE";

                    if (insertNotificationIfUnique($pdo, $userId, $s['vehicle_id'], null, $type, $title, $message, $dedupeKey)) {
                        $notificationsCreated++;
                        WebSocketHelper::notifyUser($userId, $title, $message, $type, ['vehicle_id' => $s['vehicle_id']]);
                    }
                } elseif ($status === MaintenanceCalculator::STATUS_DUE && $checkDue) {
                    $dedupeKey = "maint_due_svc_{$s['record_id']}_" . ($s['next_service_date'] ?: 'dist') . "_" . date('Y-m-d');
                    $title     = "Service Due Today";
                    $message   = sprintf("%s is due today for %s %s (%s). %s.", $s['service_type'], $s['make'], $s['model'], $s['plate_number'], $eval['reason']);
                    $type      = "DUE";

                    if (insertNotificationIfUnique($pdo, $userId, $s['vehicle_id'], null, $type, $title, $message, $dedupeKey)) {
                        $notificationsCreated++;
                        WebSocketHelper::notifyUser($userId, $title, $message, $type, ['vehicle_id' => $s['vehicle_id']]);
                    }
                } elseif ($status === MaintenanceCalculator::STATUS_UPCOMING) {
                    $dedupeKey = "maint_upcoming_svc_{$s['record_id']}_" . ($s['next_service_date'] ?: 'dist') . "_" . date('Y-m-W');
                    $title     = "Upcoming Service";
                    $message   = sprintf("%s is upcoming for %s %s (%s). %s.", $s['service_type'], $s['make'], $s['model'], $s['plate_number'], $eval['reason']);
                    $type      = "REMINDER";

                    if (insertNotificationIfUnique($pdo, $userId, $s['vehicle_id'], null, $type, $title, $message, $dedupeKey)) {
                        $notificationsCreated++;
                        WebSocketHelper::notifyUser($userId, $title, $message, $type, ['vehicle_id' => $s['vehicle_id']]);
                    }
                }
            }
        }
    }

    echo "Completed successfully. New notifications generated: {$notificationsCreated}\n";
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(0);

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    error_log("CLI Reminder Job Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(1);
}

/**
 * Inserts notification safely using deduplication key (idempotent)
 */
function insertNotificationIfUnique($pdo, $userId, $vehicleId, $scheduleId, $type, $title, $message, $dedupeKey) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, vehicle_id, type, title, message, dedupe_key, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $vehicleId, $type, $title, $message, $dedupeKey]);
        return true;
    } catch (\PDOException $e) {
        // Error code 23000 / duplicate key error is expected when already reminded
        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Duplicate')) {
            return false;
        }
        throw $e;
    }
}

