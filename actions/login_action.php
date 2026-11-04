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
    $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (isset($user['status']) && $user['status'] === 'inactive') {
            $_SESSION['flash_error'] = "Your account has been deactivated. Please contact an administrator.";
            header("Location: " . BASE_URL . "auth/login.php");
            exit;
        }

        // Prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['full_name']) . "!";
        
        // Remember Me Persistent Token
        if (!empty($_POST['remember_me'])) {
            try {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $hashedValidator = hash('sha256', $validator);
                $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days

                // Remove expired tokens for this user
                $cleanupStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND expires_at < NOW()");
                $cleanupStmt->execute([$user['id']]);

                $remStmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?)");
                $remStmt->execute([$user['id'], $selector, $hashedValidator, $expiresAt]);

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

        // Log the login
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $logStmt->execute([$user['id'], 'User logged in']);
        
        if ($user['role'] === 'admin') {
            header("Location: " . BASE_URL . "admin/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "dashboard/index.php");
        }
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
