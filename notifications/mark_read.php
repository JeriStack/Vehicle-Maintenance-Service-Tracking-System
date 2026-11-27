<?php
// notifications/mark_read.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/websocket_helper.php';

requireLogin();

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "notifications/index.php");
    exit;
}

$id = $_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    if ($id === 'all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $_SESSION['flash_success'] = "All notifications marked as read.";
        WebSocketHelper::notificationRead($userId, 'all');
    } elseif (is_numeric($id)) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        WebSocketHelper::notificationRead($userId, (int)$id);
    }
    
} catch (PDOException $e) {
    error_log("Mark Read Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
}

header("Location: " . BASE_URL . "notifications/index.php");
exit;
