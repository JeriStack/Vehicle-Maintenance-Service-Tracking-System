<?php
// actions/forgot_password_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../helpers/mail_helper.php';
require_once __DIR__ . '/../helpers/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email) || !isValidEmail($email)) {
    $_SESSION['flash_error'] = "Please enter a valid email address.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT id, full_name, email, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (!isset($user['status']) || $user['status'] !== 'inactive') {
            // Generate a secure 64-character random token
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash  = hash('sha256', $plainToken);
            $expiresAt  = date('Y-m-d H:i:s', time() + 3600); // 1 hour validity

            // Clear previous pending resets for this email
            $cleanStmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $cleanStmt->execute([$email]);

            // Save reset token
            $insStmt = $pdo->prepare("INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)");
            $insStmt->execute([$email, $tokenHash, $expiresAt]);

            // Build secure reset URL
            $resetLink = BASE_URL . "auth/reset_password.php?token=" . urlencode($plainToken) . "&email=" . urlencode($email);

            // Send password reset email
            MailHelper::sendPasswordReset($email, $user['full_name'], $resetLink);

            // Audit log
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $logStmt->execute([$user['id'], 'Requested password reset']);
        }
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

