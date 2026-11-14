<?php
// actions/login_action.php
require_once __DIR__ . '/../config/app.php';

use VMS\Security\EmailNormalizer;
use VMS\Security\PasswordHasher;
use VMS\Security\RateLimiter;
use VMS\Core\Csrf;
use VMS\Core\Session;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// 1. Validate CSRF Token
$submittedCsrf = $_POST['csrf_token'] ?? null;
if (!Csrf::validate($submittedCsrf)) {
    $_SESSION['flash_error'] = "Invalid request or security token expired. Please try again.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$rawEmail = (string) ($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if ($rawEmail === '' || $password === '') {
    $_SESSION['flash_error'] = "Please enter both email address and password.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$emailNormalized = EmailNormalizer::normalize($rawEmail);

try {
    $pdo = Database::getInstance();

    // 2. Failed-Login Throttling
    if (RateLimiter::isThrottled($ipAddress, $emailNormalized, $pdo)) {
        $cooldown = RateLimiter::getRemainingCooldown($ipAddress, $emailNormalized, $pdo);
        $_SESSION['flash_error'] = sprintf(
            "Too many failed login attempts. Please wait %d seconds before attempting to log in again.",
            max(1, $cooldown)
        );
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }

    // 3. Retrieve account safely using normalized email
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, password, role, status, auth_version 
        FROM users 
        WHERE email_normalized = ? 
        LIMIT 1
    ");
    $stmt->execute([$emailNormalized]);
    $user = $stmt->fetch();

    // 4. Credential Verification with Generic Error Response (prevent enumeration)
    $genericError = "Invalid email address or password.";

    if (!$user || !PasswordHasher::verify($password, (string) $user['password'])) {
        RateLimiter::recordFailedAttempt($ipAddress, $emailNormalized, $pdo);

        // Security Audit Log (without recording password)
        try {
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name) VALUES (?, ?, 'users')");
            $logStmt->execute([$user ? (int) $user['id'] : null, 'Failed login attempt - invalid credentials']);
        } catch (\Throwable) {}

        $_SESSION['flash_error'] = $genericError;
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }

    // 5. Account Status Check (Inactive accounts rejected with generic error to prevent enumeration)
    if (($user['status'] ?? '') !== 'active') {
        RateLimiter::recordFailedAttempt($ipAddress, $emailNormalized, $pdo);

        try {
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, 'users', ?)");
            $logStmt->execute([(int) $user['id'], 'Failed login attempt - inactive account', (int) $user['id']]);
        } catch (\Throwable) {}

        $_SESSION['flash_error'] = $genericError;
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }

    // 6. Role Recognition Validation
    $role = (string) ($user['role'] ?? '');
    if (!in_array($role, ['user', 'admin'], true)) {
        $_SESSION['flash_error'] = $genericError;
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }

    // 7. Reset Failed Attempts upon Successful Authentication
    RateLimiter::resetAttempts($ipAddress, $emailNormalized, $pdo);

    // 8. Rehash Password if Algorithm Standards or Cost Have Changed
    if (PasswordHasher::needsRehash((string) $user['password'])) {
        $newHash = PasswordHasher::hash($password);
        $rehashStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $rehashStmt->execute([$newHash, (int) $user['id']]);
    }

    // 9. Session Regeneration & Hardened Session Context
    Session::regenerate(true);
    
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['role'] = $role;
    $_SESSION['auth_version'] = (int) ($user['auth_version'] ?? 1);
    $_SESSION['authenticated_at'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . "!";

    // 10. Rotate CSRF Token at Authentication Boundary
    Csrf::rotate();

    // 11. Remember Me Persistent Token
    if (!empty($_POST['remember_me'])) {
        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $hashedValidator = hash('sha256', $validator);
            $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30));

            $cleanupStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND expires_at < NOW()");
            $cleanupStmt->execute([(int) $user['id']]);

            $remStmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?)");
            $remStmt->execute([(int) $user['id'], $selector, $hashedValidator, $expiresAt]);

            $cookieParams = [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $cookieParams['secure'] = true;
            }
            setcookie('vms_remember', $selector . ':' . $validator, $cookieParams);
        } catch (\Throwable $e) {
            error_log("Failed setting remember token: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
        }
    }

    // 12. Security Audit Log
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, 'users', ?)");
    $logStmt->execute([(int) $user['id'], 'User logged in successfully', (int) $user['id']]);

    // 13. Role-Based Dashboard Redirection
    if ($role === 'admin') {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "dashboard/index.php");
    }
    exit;

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred during login. Please try again later.";
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}
