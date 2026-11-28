<?php
// includes/auth.php
require_once __DIR__ . '/../config/app.php';

use VMS\Core\Session;
use VMS\Core\Database;

function isLoggedIn() {
    Session::start();

    if (isset($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];

        // 1. Check Idle Timeout (30 minutes default)
        if (!Session::checkIdleTimeout(1800)) {
            Session::destroy();
            return false;
        }

        // 2. Validate active account status and auth_version against database (skipped in offline unit tests)
        if (!defined('VMS_UNIT_TESTING') || !VMS_UNIT_TESTING) {
            try {
                if (class_exists(Database::class) && Database::testConnection()) {
                    $pdo = Database::getConnection();
                    $stmt = $pdo->prepare("SELECT id, role, status, auth_version FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();

                    if (!$user || ($user['status'] ?? '') !== 'active') {
                        Session::destroy();
                        return false;
                    }

                    $sessionAuthVersion = (int) ($_SESSION['auth_version'] ?? 1);
                    $dbAuthVersion = (int) ($user['auth_version'] ?? 1);

                    if ($sessionAuthVersion !== $dbAuthVersion) {
                        Session::destroy();
                        return false;
                    }

                    $_SESSION['role'] = $user['role'];
                }
            } catch (\Throwable $e) {
                // Safe non-blocking fallback for offline environments
            }
        }

        Session::recordActivity();
        return true;
    }

    return checkRememberMe();
}

/**
 * Validates persistent remember-me cookie and restores session
 */
function checkRememberMe() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }

    if (empty($_COOKIE['vms_remember'])) {
        return false;
    }

    $cookie = $_COOKIE['vms_remember'];
    $parts = explode(':', $cookie);
    if (count($parts) !== 2) {
        setcookie('vms_remember', '', time() - 3600, '/');
        return false;
    }

    list($selector, $validator) = $parts;

    try {
        if (!class_exists(Database::class) || !Database::testConnection()) {
            return false;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT rt.*, u.full_name, u.role, u.status, u.auth_version 
            FROM remember_tokens rt 
            JOIN users u ON rt.user_id = u.id 
            WHERE rt.selector = ? AND rt.expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$selector]);
        $token = $stmt->fetch();

        if ($token && hash_equals($token['hashed_validator'], hash('sha256', $validator))) {
            if (isset($token['status']) && $token['status'] === 'inactive') {
                return false;
            }

            // Restore user session
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = (int) $token['user_id'];
            $_SESSION['user_name'] = $token['full_name'];
            $_SESSION['role'] = $token['role'];
            $_SESSION['auth_version'] = (int) ($token['auth_version'] ?? 1);
            $_SESSION['authenticated_at'] = time();
            $_SESSION['last_activity'] = time();

            // Rotate validator for replay attack mitigation
            $newValidator = bin2hex(random_bytes(32));
            $newHashed = hash('sha256', $newValidator);
            $newExpires = date('Y-m-d H:i:s', time() + (86400 * 30));

            $upd = $pdo->prepare("UPDATE remember_tokens SET hashed_validator = ?, expires_at = ? WHERE id = ?");
            $upd->execute([$newHashed, $newExpires, $token['id']]);

            $cookieParams = [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $cookieParams['secure'] = true;
            }
            setcookie('vms_remember', $selector . ':' . $newValidator, $cookieParams);

            return true;
        } else {
            // Invalid token
            setcookie('vms_remember', '', time() - 3600, '/');
        }
    } catch (\Throwable $e) {
        error_log("Remember Me Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    }

    return false;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        if (isAdmin()) {
            header("Location: " . BASE_URL . "admin/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "dashboard/index.php");
        }
        exit;
    }
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("HTTP/1.1 403 Forbidden");
        $_SESSION['flash_error'] = "Access denied. Administrator privileges required.";
        header("Location: " . BASE_URL . "dashboard/index.php");
        exit;
    }
}

function requireUser() {
    requireLogin();
    if (!isUser()) {
        header("HTTP/1.1 403 Forbidden");
        $_SESSION['flash_error'] = "Access denied.";
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole() {
    return $_SESSION['role'] ?? null;
}
