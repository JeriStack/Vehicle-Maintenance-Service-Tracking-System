<?php
// actions/reset_password_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/validation.php';

use VMS\Core\Csrf;
use VMS\Security\EmailNormalizer;
use VMS\Security\PasswordHasher;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// CSRF Protection (constant-time)
if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

$token           = (string) ($_POST['token'] ?? '');
$rawEmail        = trim((string) ($_POST['email'] ?? ''));
$emailNormalized = EmailNormalizer::normalize($rawEmail);
$password        = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($token === '' || $emailNormalized === '' || !EmailNormalizer::isValid($emailNormalized)) {
    $_SESSION['flash_error'] = "Invalid password reset request.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['flash_error'] = "Password must be at least 8 characters long.";
    header("Location: " . BASE_URL . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($emailNormalized));
    exit;
}

if ($password !== $confirmPassword) {
    $_SESSION['flash_error'] = "Passwords do not match. Please try again.";
    header("Location: " . BASE_URL . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($emailNormalized));
    exit;
}

try {
    $pdo = Database::getInstance();
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$emailNormalized, $tokenHash]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $_SESSION['flash_error'] = "This password reset link is invalid or has expired. Please request a new one.";
        header("Location: " . BASE_URL . "auth/forgot_password.php");
        exit;
    }

    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email_normalized = ? LIMIT 1");
    $userStmt->execute([$emailNormalized]);
    $user = $userStmt->fetch();

    if (!$user) {
        $_SESSION['flash_error'] = "Account not found.";
        header("Location: " . BASE_URL . "auth/forgot_password.php");
        exit;
    }

    $pdo->beginTransaction();

    // 1. Update password with PasswordHasher and increment auth_version to invalidate all active sessions
    $newHashedPassword = PasswordHasher::hash($password);
    $upd = $pdo->prepare("UPDATE users SET password = ?, auth_version = auth_version + 1 WHERE id = ?");
    $upd->execute([$newHashedPassword, $user['id']]);

    // 2. Clear all reset tokens for this email
    $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $del->execute([$emailNormalized]);

    // 3. Invalidate all remember-me tokens
    $delTokens = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $delTokens->execute([$user['id']]);

    // 4. Audit log
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$user['id'], 'Password reset completed', 'users', $user['id']]);

    $pdo->commit();
    Csrf::rotate();

    $_SESSION['flash_success'] = "Your password has been successfully reset! You can now log in with your new password.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reset Password Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred while resetting your password. Please try again later.";
    header("Location: " . BASE_URL . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($emailNormalized));
    exit;
}
