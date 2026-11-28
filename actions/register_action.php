<?php
// actions/register_action.php
require_once __DIR__ . '/../config/app.php';

use VMS\Security\EmailNormalizer;
use VMS\Security\PasswordHasher;
use VMS\Core\Csrf;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

// 1. Strict CSRF Validation
$submittedCsrf = $_POST['csrf_token'] ?? null;
if (!Csrf::validate($submittedCsrf)) {
    $_SESSION['flash_error'] = "Invalid request or security token expired. Please try again.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

// 2. Reject and ignore client-supplied role parameters to prevent privilege escalation
unset($_POST['role'], $_POST['is_admin'], $_POST['is_superuser'], $_POST['permissions']);

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$rawEmail = (string) ($_POST['email'] ?? '');
$phone = trim((string) ($_POST['phone'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

// 3. Validation
if ($fullName === '' || $rawEmail === '' || $password === '' || $passwordConfirm === '') {
    $_SESSION['flash_error'] = "Please fill in all required fields.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

$emailNormalized = EmailNormalizer::normalize($rawEmail);
if (!EmailNormalizer::isValid($emailNormalized)) {
    $_SESSION['flash_error'] = "Please enter a valid email address.";
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

$policyErrors = PasswordHasher::validatePolicy($password, $passwordConfirm);
if (!empty($policyErrors)) {
    $_SESSION['flash_error'] = implode(" ", $policyErrors);
    header("Location: " . BASE_URL . "auth/register.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    
    // 4. Duplicate Check using normalized email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email_normalized = ?");
    $stmt->execute([$emailNormalized]);
    if ($stmt->fetch()) {
        $_SESSION['flash_error'] = "Email is already registered.";
        header("Location: " . BASE_URL . "auth/register.php");
        exit;
    }
    
    // 5. Cryptographic Password Hashing
    $hashedPassword = PasswordHasher::hash($password);
    
    $pdo->beginTransaction();
    
    // 6. Explicitly insert with role = 'user' (VEHICLE_OWNER) and status = 'active'
    $stmt = $pdo->prepare("
        INSERT INTO users (
            full_name, email, email_normalized, password, phone, 
            role, status, auth_version, created_at
        ) VALUES (?, ?, ?, ?, ?, 'user', 'active', 1, NOW())
    ");
    $stmt->execute([$fullName, trim($rawEmail), $emailNormalized, $hashedPassword, $phone ?: null]);
    $userId = (int) $pdo->lastInsertId();
    
    // 7. Create default notification settings and vehicle preferences
    $stmtSettings = $pdo->prepare("INSERT INTO notification_settings (user_id) VALUES (?)");
    $stmtSettings->execute([$userId]);
    
    $stmtPrefs = $pdo->prepare("INSERT INTO vehicle_preferences (user_id) VALUES (?)");
    $stmtPrefs->execute([$userId]);
    
    // 8. Audit Log
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, 'users', ?)");
    $logStmt->execute([$userId, 'Vehicle Owner registered account', $userId]);
    
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
