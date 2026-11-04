<?php
// actions/notification_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/websocket_helper.php';

requireLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;
$userId = $_SESSION['user_id'];

$pdo = Database::getInstance();

// 1. Polling fallback / AJAX unread check
if ($action === 'check_unread') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $unreadCount = (int)$stmt->fetchColumn();

        $nstmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $nstmt->execute([$userId]);
        $latest = $nstmt->fetchAll();

        echo json_encode([
            'status'       => 'ok',
            'unread_count' => $unreadCount,
            'latest'       => $latest,
            'time'         => date('Y-m-d H:i:s')
        ]);
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 2. AJAX mark as read
if ($action === 'mark_read_ajax') {
    header('Content-Type: application/json');
    try {
        if ($id === 'all' || empty($id)) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);
            WebSocketHelper::notificationRead($userId, 'all');
        } else {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([(int)$id, $userId]);
            WebSocketHelper::notificationRead($userId, (int)$id);
        }
        echo json_encode(['status' => 'ok']);
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 3. Standard web redirects
if ($action === 'delete' && $id) {
    header("Location: " . BASE_URL . "notifications/delete.php?id=" . urlencode($id));
    exit;
} elseif ($action === 'mark_read') {
    header("Location: " . BASE_URL . "notifications/mark_read.php?id=" . urlencode($id ?? 'all'));
    exit;
}

header("Location: " . BASE_URL . "notifications/index.php");
exit;
