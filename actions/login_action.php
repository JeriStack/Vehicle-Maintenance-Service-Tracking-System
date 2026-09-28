<?php
// actions/login_action.php
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request. Please try again.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['flash_error'] = "Please enter both email and password.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['full_name']) . "!";
        
        // Log the login
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $logStmt->execute([$user['id'], 'User logged in']);
        
        header("Location: " . BASE_URL . "dashboard/index.php");
        exit;
    } else {
        $_SESSION['flash_error'] = "Invalid email or password.";
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred during login. Please try again later.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}
