<?php
// services/delete.php
$pageTitle = 'Delete Service Record';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_error'] = "Invalid service record ID.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

$serviceId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    // Check permission via join
    $stmt = $pdo->prepare("
        SELECT sr.*, v.make, v.model, v.plate_number 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE sr.id = ? AND v.user_id = ?
    ");
    $stmt->execute([$serviceId, $userId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        $_SESSION['flash_error'] = "Service record not found or permission denied.";
        header("Location: " . BASE_URL . "services/index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Delete Service Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow-sm border-danger border-opacity-50">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deletion</h5>
            </div>
            <div class="card-body p-4 text-center">
                <h4 class="mb-3">Delete this service record?</h4>
                <p class="text-muted mb-4">
                    <strong><?php echo htmlspecialchars($service['service_type']); ?></strong><br>
                    Vehicle: <?php echo htmlspecialchars($service['make'] . ' ' . $service['model']); ?><br>
                    Date: <?php echo date('M d, Y', strtotime($service['service_date'])); ?><br>
                    Cost: $<?php echo number_format($service['cost'], 2); ?>
                </p>
                <div class="alert alert-warning text-start">
                    <i class="bi bi-info-circle-fill me-2"></i> This action is irreversible. The record will be permanently removed from the service history.
                </div>
                
                <form action="<?php echo BASE_URL; ?>actions/service_action.php" method="POST" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-danger px-4"><i class="bi bi-trash-fill me-1"></i> Yes, Delete Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
