<?php
// notifications/delete.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . BASE_URL . "notifications/index.php");
    exit;
}

$id = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    
    $_SESSION['flash_success'] = "Notification deleted.";
    
} catch (PDOException $e) {
    error_log("Delete Notification Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
}

header("Location: " . BASE_URL . "notifications/index.php");
exit;
