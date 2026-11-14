<?php
// auth/logout.php
require_once __DIR__ . '/../config/app.php';

use VMS\Core\Csrf;
use VMS\Core\Session;

// Handle POST logout with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? null;
    if (!Csrf::validate($submittedCsrf)) {
        $_SESSION['flash_error'] = "Security validation failed. Please try logging out again.";
        header("Location: " . BASE_URL . "dashboard/index.php");
        exit;
    }

    $userId = $_SESSION['user_id'] ?? null;

    // Clear remember-me cookie and remove token from database
    if (isset($_COOKIE['vms_remember'])) {
        $parts = explode(':', $_COOKIE['vms_remember']);
        if (count($parts) === 2) {
            try {
                $pdo = Database::getInstance();
                $del = $pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                $del->execute([$parts[0]]);
            } catch (\Throwable) {}
        }
        setcookie('vms_remember', '', time() - 3600, '/');
    }

    if ($userId) {
        try {
            $pdo = Database::getInstance();
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, 'users', ?)");
            $logStmt->execute([$userId, 'User logged out', $userId]);
        } catch (\Throwable) {}
    }

    // Invalidate and destroy session
    Session::destroy();

    // Start clean transient session for flash message
    Session::start();
    $_SESSION['flash_success'] = "You have been logged out successfully.";

    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// For GET requests, if user is not logged in, redirect directly to login
if (empty($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// Display secure logout confirmation form for GET requests (enforcing state-changing POST with CSRF)
$pageTitle = "Confirm Logout";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout - Vehicle Maintenance Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2.5rem;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="mb-3">
            <i class="bi bi-box-arrow-right text-warning" style="font-size: 3rem;"></i>
        </div>
        <h4 class="fw-bold mb-2">Confirm Logout</h4>
        <p class="text-secondary mb-4">Are you sure you want to end your active session?</p>
        
        <form action="<?php echo BASE_URL; ?>auth/logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger py-2 fw-semibold">
                    <i class="bi bi-box-arrow-right me-2"></i> Log Out
                </button>
                <a href="<?php echo BASE_URL; ?>dashboard/index.php" class="btn btn-outline-secondary py-2">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>
