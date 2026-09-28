<?php
// vehicles/delete.php
$pageTitle = 'Delete Vehicle';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_error'] = "Invalid vehicle ID.";
    header("Location: " . BASE_URL . "vehicles/index.php");
    exit;
}

$vehicleId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$vehicleId, $userId]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        $_SESSION['flash_error'] = "Vehicle not found or you don't have permission to view it.";
        header("Location: " . BASE_URL . "vehicles/index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Delete Vehicle Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "vehicles/index.php");
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
                <h4 class="mb-3">Are you sure you want to delete this vehicle?</h4>
                <p class="text-muted mb-4">
                    <strong><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'); ?></strong><br>
                    Plate Number: <?php echo htmlspecialchars($vehicle['plate_number']); ?>
                </p>
                <div class="alert alert-warning text-start">
                    <i class="bi bi-info-circle-fill me-2"></i> This action is irreversible. All associated service records and reminders will also be deleted.
                </div>
                
                <form action="<?php echo BASE_URL; ?>actions/vehicle_action.php" method="POST" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-danger px-4"><i class="bi bi-trash-fill me-1"></i> Yes, Delete Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
