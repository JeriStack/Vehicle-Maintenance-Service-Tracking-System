<?php
// actions/register_action.php
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request. Please try again.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

$fullName = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

// Validation
if (empty($fullName) || empty($email) || empty($password) || empty($passwordConfirm)) {
    $_SESSION['flash_error'] = "Please fill in all required fields.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = "Please enter a valid email address.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['flash_error'] = "Password must be at least 8 characters long.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

if ($password !== $passwordConfirm) {
    $_SESSION['flash_error'] = "Passwords do not match.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['flash_error'] = "Email is already registered.";
        header("Location: " . BASE_URL . "auth/register.php");
        exit;
    }
    
    // Hash password and insert
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$fullName, $email, $hashedPassword, $phone]);
    $userId = $pdo->lastInsertId();
    
    // Create default settings and preferences
    $stmtSettings = $pdo->prepare("INSERT INTO notification_settings (user_id) VALUES (?)");
    $stmtSettings->execute([$userId]);
    
    $stmtPrefs = $pdo->prepare("INSERT INTO vehicle_preferences (user_id) VALUES (?)");
    $stmtPrefs->execute([$userId]);
    
    // Log registration
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$userId, 'User registered', 'users', $userId]);
    
    $pdo->commit();
    
    $_SESSION['flash_success'] = "Registration successful! Please log in.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred during registration. Please try again later.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}
