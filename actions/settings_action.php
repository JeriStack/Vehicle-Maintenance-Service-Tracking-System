<?php
// actions/settings_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "settings/index.php");
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request.";
    header("Location: " . BASE_URL . "settings/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    if ($action === 'update_profile') {
        $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        
        if (empty($username) || empty($email)) {
            $_SESSION['flash_error'] = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Invalid email format.";
        } else {
            // Check if email taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = "Email is already in use by another account.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $userId]);
                $_SESSION['username'] = $username;
                $_SESSION['flash_success'] = "Profile updated successfully.";
            }
        }
        
    } elseif ($action === 'update_preferences') {
        $intervalMonths = (int)$_POST['default_service_interval'];
        $intervalMileage = (int)$_POST['default_mileage_interval'];
        $provider = trim(filter_input(INPUT_POST, 'preferred_service_provider', FILTER_SANITIZE_STRING));
        
        $stmt = $pdo->prepare("
            UPDATE vehicle_preferences SET 
                default_service_interval = ?, 
                default_mileage_interval = ?, 
                preferred_service_provider = ? 
            WHERE user_id = ?
        ");
        $stmt->execute([$intervalMonths, $intervalMileage, $provider, $userId]);
        $_SESSION['flash_success'] = "Service preferences saved.";
        
    } elseif ($action === 'update_notifications') {
        $reminderDays = (int)$_POST['reminder_days'];
        $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;
        $pushNotif = isset($_POST['push_notifications']) ? 1 : 0;
        $maintRem = isset($_POST['maintenance_reminder']) ? 1 : 0;
        $regRem = isset($_POST['registration_reminder']) ? 1 : 0;
        $dueRem = isset($_POST['service_due_reminder']) ? 1 : 0;
        $overdueRem = isset($_POST['overdue_reminder']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE notification_settings SET 
                email_notifications = ?, push_notifications = ?, reminder_days = ?,
                maintenance_reminder = ?, registration_reminder = ?, 
                service_due_reminder = ?, overdue_reminder = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$emailNotif, $pushNotif, $reminderDays, $maintRem, $regRem, $dueRem, $overdueRem, $userId]);
        $_SESSION['flash_success'] = "Notification settings saved.";
        

    }
    
} catch (PDOException $e) {
    error_log("Settings Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
}

header("Location: " . BASE_URL . "settings/index.php");
exit;
