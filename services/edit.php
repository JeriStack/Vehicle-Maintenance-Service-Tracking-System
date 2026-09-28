<?php
// services/edit.php
$pageTitle = 'Edit Service Record';
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
    
    // Fetch user's vehicles for dropdown
    $vStmt = $pdo->prepare("SELECT id, make, model, plate_number FROM vehicles WHERE user_id = ? ORDER BY make, model");
    $vStmt->execute([$userId]);
    $vehicles = $vStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Edit Service Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Service Record</h1>
    <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to History
    </a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-4">
        <form action="<?php echo BASE_URL; ?>actions/service_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
            
            <h5 class="mb-3 text-primary border-bottom pb-2">Core Service Information</h5>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="vehicle_id" class="form-label fw-medium">Vehicle <span class="text-danger">*</span></label>
                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $service['vehicle_id'] == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['plate_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="service_type" class="form-label fw-medium">Service Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="service_type" name="service_type" required>
                        <?php 
                        $types = ['Oil Change', 'Tire Rotation', 'Brake Check', 'Battery Replacement', 'Engine Service', 'Transmission Service', 'Inspection', 'Other'];
                        foreach ($types as $type) {
                            $selected = ($service['service_type'] === $type) ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="service_date" class="form-label fw-medium">Service Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="service_date" name="service_date" required value="<?php echo $service['service_date']; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="mileage" class="form-label fw-medium">Mileage at Service <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="mileage" name="mileage" required min="0" value="<?php echo htmlspecialchars($service['mileage']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Completed" <?php echo $service['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Pending" <?php echo $service['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Scheduled" <?php echo $service['status'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Service Details</h5>
            
            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label fw-medium">Description of Work</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($service['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-12 mb-3">
                    <label for="parts_replaced" class="form-label fw-medium">Parts Replaced</label>
                    <textarea class="form-control" id="parts_replaced" name="parts_replaced" rows="2"><?php echo htmlspecialchars($service['parts_replaced'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="service_provider" class="form-label fw-medium">Service Provider / Mechanic</label>
                    <input type="text" class="form-control" id="service_provider" name="service_provider" value="<?php echo htmlspecialchars($service['service_provider'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cost" class="form-label fw-medium">Cost ($) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="cost" name="cost" required min="0" step="0.01" value="<?php echo htmlspecialchars($service['cost']); ?>">
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Next Service Projection</h5>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="next_service_date" class="form-label fw-medium">Next Service Date</label>
                    <input type="date" class="form-control" id="next_service_date" name="next_service_date" value="<?php echo htmlspecialchars($service['next_service_date'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="next_service_mileage" class="form-label fw-medium">Next Service Mileage</label>
                    <input type="number" class="form-control" id="next_service_mileage" name="next_service_mileage" min="0" value="<?php echo htmlspecialchars($service['next_service_mileage'] ?? ''); ?>">
                </div>
                <div class="col-md-12 mb-3">
                    <label for="remarks" class="form-label fw-medium">Additional Remarks</label>
                    <input type="text" class="form-control" id="remarks" name="remarks" value="<?php echo htmlspecialchars($service['remarks'] ?? ''); ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Update Record</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
