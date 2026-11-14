<?php
// actions/admin_user_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

use VMS\Security\AdminSecurity;
use VMS\Core\Csrf;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

// 1. Strict CSRF Validation
$submittedCsrf = $_POST['csrf_token'] ?? null;
if (!Csrf::validate($submittedCsrf)) {
    $_SESSION['flash_error'] = "Invalid request or security token expired. Please try again.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

$action = (string) ($_POST['action'] ?? '');
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$adminPassword = (string) ($_POST['admin_password'] ?? '');

if (!$userId || $action === '') {
    $_SESSION['flash_error'] = "Invalid input data.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    $currentAdminId = (int) currentUserId();

    if ($action === 'change_role') {
        $newRole = (string) ($_POST['new_role'] ?? '');

        // Execute role change with re-auth, last-admin protection, and session invalidation
        $result = AdminSecurity::changeUserRole($pdo, $currentAdminId, $userId, $newRole, $adminPassword);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: " . BASE_URL . "admin/user_view.php?id=" . $userId);
        exit;
    }

    if ($action === 'toggle_status') {
        $newStatus = (string) ($_POST['new_status'] ?? '');

        // Execute status change with re-auth, last-admin protection, and session invalidation
        $result = AdminSecurity::toggleUserStatus($pdo, $currentAdminId, $userId, $newStatus, $adminPassword);

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: " . BASE_URL . "admin/user_view.php?id=" . $userId);
        exit;
    }

    $_SESSION['flash_error'] = "Unknown action requested.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;

} catch (\Throwable $e) {
    error_log("Admin Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred while processing the administrative action.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}
