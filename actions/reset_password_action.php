<?php
// actions/reset_password_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

$token           = $_POST['token'] ?? '';
$email           = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($email) || !isValidEmail($email)) {
    $_SESSION['flash_error'] = "Invalid password reset request.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['flash_error'] = "Password must be at least 6 characters long.";
    header("Location: " . BASE_URL . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email));
    exit;
}

if ($password !== $confirmPassword) {
    $_SESSION['flash_error'] = "Passwords do not match. Please try again.";
    header("Location: " . BASE_URL . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email));
    exit;
}

try {
    $pdo = Database::getInstance();
    $tokenHash = hash('sha256', $token);

    // Verify token validity
    $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$email, $tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $_SESSION['flash_error'] = "This password reset link is invalid or has expired. Please request a new one.";
        header("Location: " . BASE_URL . "auth/forgot_password.php");
        exit;
    }

    // Get user id
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$email]);
    $user = $userStmt->fetch();

    if (!$user) {
        $_SESSION['flash_error'] = "Account not found.";
        header("Location: " . BASE_URL . "auth/forgot_password.php");
        exit;
    }

    $pdo->beginTransaction();

    // 1. Update password
    $newHashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->execute([$newHashedPassword, $user['id']]);

    // 2. Clear all reset tokens for this email
    $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->execute([$email]);

    // 3. Invalidate any existing remember-me sessions for security
    $delTokens = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $delTokens->execute([$user['id']]);

    // 4. Log the audit event
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
    $logStmt->execute([$user['id'], 'Password reset completed']);

    $pdo->commit();

    $_SESSION['flash_success'] = "Your password has been successfully reset! You can now log in with your new password.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reset Password Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred while resetting your password. Please try again later.";
    header("Location: " . BASE_URL . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email));
    exit;
}

