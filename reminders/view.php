<?php
// reminders/view.php
$pageTitle = 'View Reminder';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "reminders/index.php");
    exit;
}

$id = $_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT n.*, v.make, v.model FROM notifications n LEFT JOIN vehicles v ON n.vehicle_id = v.id WHERE n.id = ? AND n.user_id = ? AND n.type = 'REMINDER'");
    $stmt->execute([$id, $userId]);
    $reminder = $stmt->fetch();
    
    if ($reminder && !$reminder['is_read']) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
    }
} catch (PDOException $e) {
    error_log("View Reminder Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
}

if (!$reminder) {
    $_SESSION['flash_error'] = "Reminder not found.";
    header("Location: " . BASE_URL . "reminders/index.php");
    exit;
}
?>

<div class="card shadow-sm border-0 border-top border-warning border-3 mb-4">
    <div class="card-body p-5 text-center">
        <i class="bi bi-calendar-event display-1 text-warning opacity-50 mb-3"></i>
        <h2 class="fw-bold mb-3"><?php echo htmlspecialchars($reminder['title']); ?></h2>
        <p class="fs-5 text-muted mb-4"><?php echo htmlspecialchars($reminder['message']); ?></p>
        
        <div class="d-flex justify-content-center gap-3">
            <a href="<?php echo BASE_URL; ?>reminders/index.php" class="btn btn-outline-secondary">Back</a>
            <a href="<?php echo BASE_URL; ?>services/create.php?vehicle_id=<?php echo $reminder['vehicle_id']; ?>" class="btn btn-primary">Log Service</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
