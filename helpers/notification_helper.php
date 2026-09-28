<?php
// helpers/notification_helper.php
require_once __DIR__ . '/../config/app.php';

class NotificationHelper {
    
    /**
     * Generate notifications based on vehicle and service status
     * Should be called periodically or on dashboard load
     */
    public static function generateReminders($userId) {
        try {
            $pdo = Database::getInstance();
            
            // 1. Get user settings
            $stmtSet = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
            $stmtSet->execute([$userId]);
            $settings = $stmtSet->fetch();
            
            if (!$settings) return; // No settings, abort
            
            $reminderDays = (int)$settings['reminder_days'];
            
            // 2. Registration Expiry Reminders
            if ($settings['registration_reminder']) {
                $vStmt = $pdo->prepare("
                    SELECT id, make, model, plate_number, registration_expiry 
                    FROM vehicles 
                    WHERE user_id = ? AND registration_expiry IS NOT NULL
                    AND registration_expiry <= DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
                ");
                $vStmt->execute([$userId, $reminderDays]);
                $vehicles = $vStmt->fetchAll();
                
                foreach ($vehicles as $v) {
                    $days = (strtotime($v['registration_expiry']) - time()) / (60 * 60 * 24);
                    
                    $type = $days < 0 ? 'OVERDUE' : 'REMINDER';
                    $title = $days < 0 ? 'Registration Expired' : 'Registration Expiring Soon';
                    
                    if ($days < 0) {
                        $message = sprintf("Registration for %s %s (%s) has expired.", $v['make'], $v['model'], $v['plate_number']);
                    } else {
                        $message = sprintf("Registration for %s %s (%s) expires in %d days.", $v['make'], $v['model'], $v['plate_number'], ceil($days));
                    }
                    
                    self::createNotificationIfNotExists($pdo, $userId, $v['id'], $type, $title, $message);
                }
            }
            
            // 3. Service Due / Upcoming Reminders
            if ($settings['maintenance_reminder'] || $settings['service_due_reminder'] || $settings['overdue_reminder']) {
                
                // Get completed services that have next_service projection
                $sStmt = $pdo->prepare("
                    SELECT sr.id as record_id, sr.service_type, sr.next_service_date, sr.next_service_mileage,
                           v.id as vehicle_id, v.make, v.model, v.plate_number, v.current_mileage
                    FROM service_records sr
                    JOIN vehicles v ON sr.vehicle_id = v.id
                    WHERE v.user_id = ? AND sr.status = 'Completed'
                ");
                $sStmt->execute([$userId]);
                $records = $sStmt->fetchAll();
                
                foreach ($records as $r) {
                    $daysDiff = null;
                    if ($r['next_service_date']) {
                        $daysDiff = (strtotime($r['next_service_date']) - time()) / (60 * 60 * 24);
                    }
                    
                    $mileageDiff = null;
                    if ($r['next_service_mileage']) {
                        $mileageDiff = $r['next_service_mileage'] - $r['current_mileage'];
                    }
                    
                    // Overdue
                    if ($settings['overdue_reminder'] && (($daysDiff !== null && $daysDiff < 0) || ($mileageDiff !== null && $mileageDiff < 0))) {
                        $title = "Service Overdue";
                        $message = sprintf("%s is overdue for %s %s (%s).", $r['service_type'], $r['make'], $r['model'], $r['plate_number']);
                        self::createNotificationIfNotExists($pdo, $userId, $r['vehicle_id'], 'OVERDUE', $title, $message);
                    }
                    // Due Today
                    elseif ($settings['service_due_reminder'] && (($daysDiff !== null && $daysDiff >= 0 && $daysDiff < 1) || ($mileageDiff !== null && $mileageDiff == 0))) {
                        $title = "Service Due Today";
                        $message = sprintf("%s is due today for %s %s (%s).", $r['service_type'], $r['make'], $r['model'], $r['plate_number']);
                        self::createNotificationIfNotExists($pdo, $userId, $r['vehicle_id'], 'DUE', $title, $message);
                    }
                    // Upcoming
                    elseif ($settings['maintenance_reminder'] && (($daysDiff !== null && $daysDiff > 0 && $daysDiff <= $reminderDays) || ($mileageDiff !== null && $mileageDiff > 0 && $mileageDiff <= 1000))) {
                        $title = "Upcoming Service";
                        $message = sprintf("%s will be due soon for %s %s (%s).", $r['service_type'], $r['make'], $r['model'], $r['plate_number']);
                        self::createNotificationIfNotExists($pdo, $userId, $r['vehicle_id'], 'REMINDER', $title, $message);
                    }
                }
            }
            
        } catch (PDOException $e) {
            error_log("Notification Helper Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
        }
    }
    
    /**
     * Prevents duplicate spamming of notifications
     */
    private static function createNotificationIfNotExists($pdo, $userId, $vehicleId, $type, $title, $message) {
        // Check if a similar unread notification exists within the last 7 days
        $checkStmt = $pdo->prepare("
            SELECT id FROM notifications 
            WHERE user_id = ? AND vehicle_id = ? AND type = ? AND title = ?
            AND (is_read = 0 OR created_at > DATE_SUB(NOW(), INTERVAL 7 DAY))
            LIMIT 1
        ");
        $checkStmt->execute([$userId, $vehicleId, $type, $title]);
        
        if (!$checkStmt->fetch()) {
            $insStmt = $pdo->prepare("INSERT INTO notifications (user_id, vehicle_id, type, title, message) VALUES (?, ?, ?, ?, ?)");
            $insStmt->execute([$userId, $vehicleId, $type, $title, $message]);
        }
    }
}
