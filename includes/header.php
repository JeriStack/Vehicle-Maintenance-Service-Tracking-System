<?php
// includes/header.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth.php';

// Protect pages that include header (unless specifically allowed)
requireLogin();

// Generate automatic reminders
require_once __DIR__ . '/../helpers/notification_helper.php';
NotificationHelper::generateReminders($_SESSION['user_id']);

// Fetch unread notifications count
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Ignore error for UI
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/dashboard.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/responsive.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <!-- Wrapper -->
    <div class="d-flex" id="wrapper">
        
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="w-100">
            
            <?php require_once __DIR__ . '/navbar.php'; ?>
            
            <!-- Main Content Area -->
            <div class="container-fluid p-4 main-content">
                <?php require_once __DIR__ . '/flash.php'; displayFlashMessages(); ?>
