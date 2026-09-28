<?php
// reminders/index.php
$pageTitle = 'Reminders';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("
        SELECT n.*, v.make, v.model, v.plate_number 
        FROM notifications n
        LEFT JOIN vehicles v ON n.vehicle_id = v.id
        WHERE n.user_id = ? AND n.type = 'REMINDER'
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$userId]);
    $reminders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Reminders Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $reminders = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-calendar-check text-warning me-2"></i>Active Reminders</h1>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($reminders)): ?>
            <div class="text-center p-5 text-muted">No active reminders found.</div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($reminders as $r): ?>
                    <a href="<?php echo BASE_URL; ?>reminders/view.php?id=<?php echo $r['id']; ?>" class="list-group-item list-group-item-action p-4 <?php echo $r['is_read'] ? '' : 'fw-bold'; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 text-dark"><?php echo htmlspecialchars($r['title']); ?></h5>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></small>
                        </div>
                        <p class="mb-1 text-secondary"><?php echo htmlspecialchars($r['message']); ?></p>
                        <small class="text-primary">Click to view details</small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
