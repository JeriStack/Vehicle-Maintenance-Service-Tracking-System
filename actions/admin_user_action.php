<?php
// actions/admin_user_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request. Please try again.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId || !$action) {
    $_SESSION['flash_error'] = "Invalid input.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    
    if ($action === 'change_role') {
        $newRole = filter_input(INPUT_POST, 'new_role', FILTER_SANITIZE_STRING);
        if (!in_array($newRole, ['user', 'admin'])) {
            $_SESSION['flash_error'] = "Invalid role selected.";
            header("Location: " . BASE_URL . "admin/user_view.php?id=" . $userId);
            exit;
        }
        
        // Prevent self-demotion
        if ($userId === currentUserId()) {
            $_SESSION['flash_error'] = "You cannot change your own role.";
            header("Location: " . BASE_URL . "admin/user_view.php?id=" . $userId);
            exit;
        }
        
        // If demoting an admin to user, make sure they are not the last admin
        if ($newRole === 'user') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            if ($stmt->fetchColumn() <= 1) {
                // Check if this user is an admin
                $cStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $cStmt->execute([$userId]);
                if ($cStmt->fetchColumn() === 'admin') {
                    $_SESSION['flash_error'] = "Cannot demote the last administrator in the system.";
                    header("Location: " . BASE_URL . "admin/user_view.php?id=" . $userId);
                    exit;
                }
            }
        }
        
        // Get old role for logging
        $cStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $cStmt->execute([$userId]);
        $oldRole = $cStmt->fetchColumn();
        
        if ($oldRole !== $newRole) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            
            // Log action
            $logMsg = "Changed role from $oldRole to $newRole";
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $logStmt->execute([currentUserId(), $logMsg, 'users', $userId]);
            
            $_SESSION['flash_success'] = "User role updated successfully.";
        } else {
            $_SESSION['flash_success'] = "Role unchanged.";
        }
        
        header("Location: " . BASE_URL . "admin/user_view.php?id=" . $userId);
        exit;
    }
    
    $_SESSION['flash_error'] = "Unknown action.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
    
} catch (PDOException $e) {
    error_log("Admin Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred. Please try again later.";
    header("Location: " . BASE_URL . "admin/users.php");
    exit;
}
