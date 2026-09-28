<?php
// notifications/index.php
$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("
        SELECT n.*, v.make, v.model, v.plate_number 
        FROM notifications n
        LEFT JOIN vehicles v ON n.vehicle_id = v.id
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    
    $hasUnread = false;
    foreach ($notifications as $n) {
        if ($n['is_read'] == 0) $hasUnread = true;
    }

} catch (PDOException $e) {
    error_log("Notifications Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $notifications = [];
    echo '<div class="alert alert-danger">Failed to load notifications.</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-bell text-primary me-2"></i>Notifications</h1>
    <?php if ($hasUnread): ?>
        <a href="<?php echo BASE_URL; ?>notifications/mark_read.php?id=all" class="btn btn-outline-primary btn-sm shadow-sm">
            <i class="bi bi-check2-all me-1"></i> Mark All as Read
        </a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center p-5">
                <div class="display-1 text-muted mb-3 opacity-25"><i class="bi bi-bell-slash"></i></div>
                <h4 class="text-secondary">No notifications</h4>
                <p class="text-muted">You're all caught up!</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <div class="list-group-item p-4 <?php echo $n['is_read'] ? 'bg-white' : 'bg-light'; ?>">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1 fw-bold <?php echo $n['is_read'] ? 'text-secondary' : 'text-primary'; ?>">
                                    <?php if ($n['type'] === 'OVERDUE'): ?>
                                        <i class="bi bi-exclamation-circle text-danger me-2"></i>
                                    <?php elseif ($n['type'] === 'REMINDER'): ?>
                                        <i class="bi bi-calendar-event text-warning text-dark me-2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-info-circle text-info me-2"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($n['title']); ?>
                                </h5>
                                <p class="mb-2 text-dark fs-6"><?php echo htmlspecialchars($n['message']); ?></p>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?>
                                </small>
                            </div>
                            <div class="d-flex flex-column gap-2 text-end">
                                <?php if (!$n['is_read']): ?>
                                    <a href="<?php echo BASE_URL; ?>notifications/mark_read.php?id=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill" title="Mark as read">
                                        <i class="bi bi-check2"></i> Mark Read
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>notifications/delete.php?id=<?php echo $n['id']; ?>" class="btn btn-sm btn-outline-danger px-3 rounded-pill" title="Delete notification" onclick="return confirm('Delete this notification?');">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
