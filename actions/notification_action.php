<?php
// actions/notification_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null;
    
    if ($action === 'delete' && $id) {
        header("Location: " . BASE_URL . "notifications/delete.php?id=" . urlencode($id));
        exit;
    } elseif ($action === 'mark_read') {
        header("Location: " . BASE_URL . "notifications/mark_read.php?id=" . urlencode($id ?? 'all'));
        exit;
    }
}

header("Location: " . BASE_URL . "notifications/index.php");
exit;
