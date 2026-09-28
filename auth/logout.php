<?php
// auth/logout.php
require_once __DIR__ . '/../config/app.php';

$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    try {
        $pdo = Database::getInstance();
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $logStmt->execute([$userId, 'User logout']);
    } catch (PDOException $e) {
        // Ignore logging errors on logout
    }
}

// Unset all session values
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session just for the flash message
session_start();
$_SESSION['flash_success'] = "You have been logged out successfully.";

header("Location: " . BASE_URL . "auth/login.php");
exit;
