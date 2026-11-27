<?php
// actions/forgot_password_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/mail_helper.php';
require_once __DIR__ . '/../helpers/validation.php';

use VMS\Core\Csrf;
use VMS\Security\EmailNormalizer;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

// CSRF Protection (constant-time)
if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

$rawEmail = trim((string) ($_POST['email'] ?? ''));
$emailNormalized = EmailNormalizer::normalize($rawEmail);

if ($emailNormalized === '' || !EmailNormalizer::isValid($emailNormalized)) {
    $_SESSION['flash_error'] = "Please enter a valid email address.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT id, full_name, email, email_normalized, status FROM users WHERE email_normalized = ? LIMIT 1");
    $stmt->execute([$emailNormalized]);
    $user = $stmt->fetch();

    if ($user && (!isset($user['status']) || $user['status'] !== 'inactive')) {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash  = hash('sha256', $plainToken);
        $expiresAt  = date('Y-m-d H:i:s', time() + 3600);

        $cleanStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $cleanStmt->execute([$emailNormalized]);

        $insStmt = $pdo->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
        $insStmt->execute([$emailNormalized, $tokenHash, $expiresAt]);

        $resetLink = BASE_URL . "auth/reset_password.php?token=" . urlencode($plainToken) . "&email=" . urlencode($emailNormalized);
        MailHelper::sendPasswordReset($user['email'], $user['full_name'], $resetLink);

        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $logStmt->execute([$user['id'], 'Requested password reset']);
    }

    // Always show generic message to avoid email enumeration
    $_SESSION['flash_success'] = "If an account exists with that email address, a password reset link has been sent. Please check your inbox or spam folder.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;

} catch (PDOException $e) {
    error_log("Forgot Password Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred while processing your request. Please try again later.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}
