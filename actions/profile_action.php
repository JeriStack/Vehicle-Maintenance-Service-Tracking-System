<?php
// actions/profile_action.php
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
        $fullName = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        
        if (empty($fullName) || empty($email)) {
            $_SESSION['flash_error'] = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Invalid email format.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = "Email is already in use by another account.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $userId]);
                $_SESSION['user_name'] = $fullName;
                $_SESSION['flash_success'] = "Profile updated successfully.";
            }
        }
        header("Location: " . BASE_URL . "settings/profile.php");
        exit;
        
    } elseif ($action === 'update_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        
        if (!password_verify($current, $hash)) {
            $_SESSION['flash_error'] = "Current password is incorrect.";
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_error'] = "New password must be at least 8 characters.";
        } elseif ($new !== $confirm) {
            $_SESSION['flash_error'] = "New passwords do not match.";
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $upd->execute([$newHash, $userId]);
            $_SESSION['flash_success'] = "Password updated securely.";
            
            // Log action
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'Password change', 'users', $userId]);
        }
        header("Location: " . BASE_URL . "auth/change_password.php");
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Profile Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
}

header("Location: " . BASE_URL . "settings/index.php");
exit;
