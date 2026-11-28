<?php
// actions/settings_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

use VMS\Core\Csrf;
use VMS\Security\EmailNormalizer;

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "settings/index.php");
    exit;
}

if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Invalid request or security token expired. Please try again.";
    header("Location: " . BASE_URL . "settings/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();

    if ($action === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $rawEmail = trim((string) ($_POST['email'] ?? ''));
        $emailNormalized = EmailNormalizer::normalize($rawEmail);

        if ($fullName === '' || $rawEmail === '') {
            $_SESSION['flash_error'] = "All fields are required.";
        } elseif (!EmailNormalizer::isValid($emailNormalized)) {
            $_SESSION['flash_error'] = "Invalid email format.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email_normalized = ? AND id != ?");
            $stmt->execute([$emailNormalized, $userId]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = "Email is already in use by another account.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, email_normalized = ? WHERE id = ?");
                $stmt->execute([$fullName, $rawEmail, $emailNormalized, $userId]);
                $_SESSION['user_name'] = $fullName;
                $_SESSION['flash_success'] = "Profile updated successfully.";
            }
        }

    } elseif ($action === 'update_preferences') {
        $intervalMonths = max(1, min(60, (int) ($_POST['default_service_interval'] ?? 6)));
        $intervalMileage = max(500, min(100000, (int) ($_POST['default_mileage_interval'] ?? 5000)));
        $provider = trim((string) ($_POST['preferred_service_provider'] ?? ''));

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_preferences (user_id, default_service_interval, default_mileage_interval, preferred_service_provider)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                default_service_interval = VALUES(default_service_interval),
                default_mileage_interval = VALUES(default_mileage_interval),
                preferred_service_provider = VALUES(preferred_service_provider)
        ");
        $stmt->execute([$userId, $intervalMonths, $intervalMileage, $provider]);
        $_SESSION['flash_success'] = "Service preferences saved.";

    } elseif ($action === 'update_notifications') {
        $reminderDays = max(1, min(90, (int) ($_POST['reminder_days'] ?? 7)));
        $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;
        $pushNotif = isset($_POST['push_notifications']) ? 1 : 0;
        $maintRem = isset($_POST['maintenance_reminder']) ? 1 : 0;
        $regRem = isset($_POST['registration_reminder']) ? 1 : 0;
        $dueRem = isset($_POST['service_due_reminder']) ? 1 : 0;
        $overdueRem = isset($_POST['overdue_reminder']) ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO notification_settings (
                user_id, email_notifications, push_notifications, reminder_days,
                maintenance_reminder, registration_reminder, service_due_reminder, overdue_reminder
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                email_notifications = VALUES(email_notifications),
                push_notifications = VALUES(push_notifications),
                reminder_days = VALUES(reminder_days),
                maintenance_reminder = VALUES(maintenance_reminder),
                registration_reminder = VALUES(registration_reminder),
                service_due_reminder = VALUES(service_due_reminder),
                overdue_reminder = VALUES(overdue_reminder)
        ");
        $stmt->execute([$userId, $emailNotif, $pushNotif, $reminderDays, $maintRem, $regRem, $dueRem, $overdueRem]);
        $_SESSION['flash_success'] = "Notification settings saved.";
    }

} catch (PDOException $e) {
    error_log("Settings Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
}

header("Location: " . BASE_URL . "settings/index.php");
exit;
