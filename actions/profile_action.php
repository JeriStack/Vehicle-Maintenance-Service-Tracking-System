<?php
// actions/profile_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

use VMS\Core\Csrf;
use VMS\Core\Session;
use VMS\Security\EmailNormalizer;
use VMS\Security\PasswordHasher;

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "settings/index.php");
    exit;
}

if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Invalid request or security token expired. Please try again.";
    header("Location: " . BASE_URL . "settings/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();

    if ($action === 'update_profile') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $rawEmail = trim((string) ($_POST['email'] ?? ''));
        $emailNormalized = EmailNormalizer::normalize($rawEmail);

        if ($fullName === '' || $rawEmail === '') {
            $_SESSION['flash_error'] = "All fields are required.";
        } elseif (!EmailNormalizer::isValid($emailNormalized)) {
            $_SESSION['flash_error'] = "Invalid email format.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email_normalized = ? AND id != ?");
            $stmt->execute([$emailNormalized, $userId]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = "Email is already in use by another account.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, email_normalized = ? WHERE id = ?");
                $stmt->execute([$fullName, $rawEmail, $emailNormalized, $userId]);
                $_SESSION['user_name'] = $fullName;
                $_SESSION['flash_success'] = "Profile updated successfully.";
            }
        }
        header("Location: " . BASE_URL . "settings/profile.php");
        exit;

    } elseif ($action === 'update_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $stmt = $pdo->prepare("SELECT password, auth_version FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch();

        if (!$userRow || !PasswordHasher::verify($current, (string) $userRow['password'])) {
            $_SESSION['flash_error'] = "Current password is incorrect.";
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_error'] = "New password must be at least 8 characters.";
        } elseif ($new !== $confirm) {
            $_SESSION['flash_error'] = "New passwords do not match.";
        } else {
            $pdo->beginTransaction();

            $newHash = PasswordHasher::hash($new);
            $newAuthVersion = ((int) ($userRow['auth_version'] ?? 1)) + 1;

            $upd = $pdo->prepare("UPDATE users SET password = ?, auth_version = ? WHERE id = ?");
            $upd->execute([$newHash, $newAuthVersion, $userId]);

            // Revoke persistent remember-me tokens on password change
            $delTokens = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $delTokens->execute([$userId]);

            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$userId, 'Password change', 'users', $userId]);

            $pdo->commit();

            // Keep the current session valid while invalidating all other sessions
            Session::regenerate();
            $_SESSION['auth_version'] = $newAuthVersion;
            Csrf::rotate();

            $_SESSION['flash_success'] = "Password updated securely.";
        }
        header("Location: " . BASE_URL . "auth/change_password.php");
        exit;
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Profile Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
}

header("Location: " . BASE_URL . "settings/index.php");
exit;
