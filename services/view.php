<?php
// services/view.php
$pageTitle = 'View Service Record';
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
    
    // Check permission and get record
    $stmt = $pdo->prepare("
        SELECT sr.*, v.make, v.model, v.plate_number, v.year, v.color 
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
    error_log("View Service Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Service Record Details</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>services/edit.php?id=<?php echo $serviceId; ?>" class="btn btn-outline-primary shadow-sm me-2">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <!-- Summary Card -->
        <div class="card shadow-sm border-0 h-100 bg-light">
            <div class="card-body p-4 text-center">
                <div class="mb-3">
                    <div class="display-3 text-primary opacity-50">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($service['service_type']); ?></h4>
                <p class="text-muted mb-4"><?php echo date('F d, Y', strtotime($service['service_date'])); ?></p>
                
                <h2 class="fw-bold text-dark mb-4">$<?php echo number_format($service['cost'], 2); ?></h2>
                
                <div class="d-flex justify-content-center gap-3 mb-4">
                    <div class="text-center">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Status</div>
                        <?php if ($service['status'] === 'Completed'): ?>
                            <span class="badge bg-success px-3">Completed</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-3"><?php echo htmlspecialchars($service['status']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-center border-start ps-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Mileage</div>
                        <span class="fw-medium"><?php echo number_format($service['mileage']); ?> km</span>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-white border-top p-3 text-center">
                <div class="small text-muted text-uppercase fw-semibold mb-1">Vehicle</div>
                <a href="<?php echo BASE_URL; ?>vehicles/view.php?id=<?php echo $service['vehicle_id']; ?>" class="text-decoration-none fw-bold">
                    <?php echo htmlspecialchars($service['make'] . ' ' . $service['model'] . ' (' . $service['plate_number'] . ')'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8 mb-4">
        <!-- Detailed Information -->
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Service Details</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <small class="text-muted d-block mb-1 text-uppercase fw-semibold">Description of Work</small>
                    <div class="p-3 bg-light rounded text-dark">
                        <?php echo !empty($service['description']) ? nl2br(htmlspecialchars($service['description'])) : '<span class="text-muted fst-italic">No description provided</span>'; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <small class="text-muted d-block mb-1 text-uppercase fw-semibold">Parts Replaced</small>
                    <div class="p-3 bg-light rounded text-dark">
                        <?php echo !empty($service['parts_replaced']) ? nl2br(htmlspecialchars($service['parts_replaced'])) : '<span class="text-muted fst-italic">No parts listed</span>'; ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <small class="text-muted d-block mb-1 text-uppercase fw-semibold">Service Provider</small>
                        <div class="fw-medium text-dark">
                            <i class="bi bi-shop me-2 text-muted"></i>
                            <?php echo !empty($service['service_provider']) ? htmlspecialchars($service['service_provider']) : 'Not specified'; ?>
                        </div>
                    </div>
                </div>
                
                <hr class="text-muted opacity-25">
                
                <h6 class="fw-bold text-dark mb-3 mt-4">Next Service Projection</h6>
                
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <small class="text-muted d-block mb-1">Next Service Date</small>
                        <div class="fw-medium">
                            <?php echo !empty($service['next_service_date']) ? date('F d, Y', strtotime($service['next_service_date'])) : 'Not specified'; ?>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <small class="text-muted d-block mb-1">Next Service Mileage</small>
                        <div class="fw-medium">
                            <?php echo !empty($service['next_service_mileage']) ? number_format($service['next_service_mileage']) . ' km' : 'Not specified'; ?>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <small class="text-muted d-block mb-1">Remarks for next service</small>
                        <div class="fw-medium text-dark">
                            <?php echo !empty($service['remarks']) ? htmlspecialchars($service['remarks']) : 'None'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
