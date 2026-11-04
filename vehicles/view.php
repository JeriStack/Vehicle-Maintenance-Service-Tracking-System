<?php
// vehicles/view.php
$pageTitle = 'View Vehicle';
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
    
    // Fetch recent service records for this vehicle
    $srvStmt = $pdo->prepare("SELECT * FROM service_records WHERE vehicle_id = ? ORDER BY service_date DESC LIMIT 5");
    $srvStmt->execute([$vehicleId]);
    $recentServices = $srvStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("View Vehicle Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "vehicles/index.php");
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Vehicle Details</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>vehicles/edit.php?id=<?php echo $vehicleId; ?>" class="btn btn-outline-primary shadow-sm me-2">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <!-- Vehicle Card -->
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <div class="display-1 text-primary opacity-50">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($vehicle['year'] . ' • ' . $vehicle['color']); ?></p>
                <div class="d-inline-block border rounded px-3 py-2 mb-4 bg-light shadow-sm">
                    <span class="fs-5 fw-bold tracking-wider"><?php echo htmlspecialchars($vehicle['plate_number']); ?></span>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <div class="text-center">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Status</div>
                        <?php if ($vehicle['status'] === 'Active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($vehicle['status']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-center border-start ps-3">
                        <div class="small text-muted text-uppercase fw-semibold mb-1">Type</div>
                        <span class="fw-medium"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Technical & Registration Information</h6>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3">
                        <small class="text-muted d-block mb-1">Current Mileage</small>
                        <div class="fw-bold fs-5"><?php echo number_format($vehicle['current_mileage']); ?> <span class="fs-6 fw-normal text-muted">km</span></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <small class="text-muted d-block mb-1">VIN / Chassis Number</small>
                        <div class="fw-medium"><?php echo htmlspecialchars($vehicle['vin_number'] ?: 'Not specified'); ?></div>
                    </div>
                </div>
                
                <hr class="text-muted opacity-25">
                
                <div class="row mt-4">
                    <div class="col-sm-6 mb-3">
                        <small class="text-muted d-block mb-1">Registration Date</small>
                        <div class="fw-medium">
                            <?php echo !empty($vehicle['registration_date']) ? date('F d, Y', strtotime($vehicle['registration_date'])) : 'Not specified'; ?>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <small class="text-muted d-block mb-1">Registration Expiry</small>
                        <div class="fw-medium">
                            <?php 
                            if (!empty($vehicle['registration_expiry'])) {
                                $expiry = strtotime($vehicle['registration_expiry']);
                                $diffDays = ($expiry - time()) / (60 * 60 * 24);
                                $colorClass = $diffDays < 0 ? 'text-danger fw-bold' : ($diffDays < 30 ? 'text-warning text-dark fw-bold' : 'text-success');
                                echo '<span class="' . $colorClass . '">' . date('F d, Y', $expiry) . '</span>';
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-dark">Recent Service Records</h6>
        <a href="<?php echo BASE_URL; ?>services/create.php?vehicle_id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus"></i> Add Service
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentServices)): ?>
            <div class="p-4 text-center text-muted">
                <p class="mb-0">No service records found for this vehicle.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Service Type</th>
                            <th>Mileage</th>
                            <th>Cost</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentServices as $srv): ?>
                            <tr>
                                <td class="ps-4"><?php echo date('M d, Y', strtotime($srv['service_date'])); ?></td>
                                <td><a href="<?php echo BASE_URL; ?>services/view.php?id=<?php echo $srv['id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($srv['service_type']); ?></a></td>
                                <td><?php echo number_format($srv['mileage']); ?></td>
                                <td>$<?php echo number_format($srv['cost'], 2); ?></td>
                                <td class="pe-4">
                                    <?php if ($srv['status'] === 'Completed'): ?>
                                        <span class="badge bg-success rounded-pill">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill"><?php echo htmlspecialchars($srv['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 text-center border-top">
                <a href="<?php echo BASE_URL; ?>services/index.php?vehicle_id=<?php echo $vehicleId; ?>" class="text-decoration-none fw-medium">View All History</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
