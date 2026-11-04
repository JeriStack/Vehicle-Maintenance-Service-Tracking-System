<?php
// services/create.php
$pageTitle = 'Add Service Record';
require_once __DIR__ . '/../includes/header.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = $_SESSION['user_id'];
$preSelectedVehicle = isset($_GET['vehicle_id']) && is_numeric($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;

try {
    $pdo = Database::getInstance();
    
    // Fetch all user's vehicles for the dropdown
    $vStmt = $pdo->prepare("SELECT id, make, model, plate_number FROM vehicles WHERE user_id = ? ORDER BY make, model");
    $vStmt->execute([$userId]);
    $vehicles = $vStmt->fetchAll();
    
    if (empty($vehicles)) {
        $_SESSION['flash_warning'] = "Please add a vehicle before creating a service record.";
        header("Location: " . BASE_URL . "vehicles/create.php");
        exit;
    }
    
    // Fetch preferences for default intervals
    $pStmt = $pdo->prepare("SELECT default_service_interval, default_mileage_interval, preferred_service_provider FROM vehicle_preferences WHERE user_id = ?");
    $pStmt->execute([$userId]);
    $prefs = $pStmt->fetch();
    
} catch (PDOException $e) {
    error_log("Service Create Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Add Service Record</h1>
    <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to History
    </a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-4">
        <form action="<?php echo BASE_URL; ?>actions/service_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="create">
            
            <h5 class="mb-3 text-primary border-bottom pb-2">Core Service Information</h5>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="vehicle_id" class="form-label fw-medium">Vehicle <span class="text-danger">*</span></label>
                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                        <option value="">Select a vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $preSelectedVehicle === $v['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['plate_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="service_type" class="form-label fw-medium">Service Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="service_type" name="service_type" required>
                        <option value="">Select service type</option>
                        <option value="Oil Change">Oil Change</option>
                        <option value="Tire Rotation">Tire Rotation</option>
                        <option value="Brake Check">Brake Check</option>
                        <option value="Battery Replacement">Battery Replacement</option>
                        <option value="Engine Service">Engine Service</option>
                        <option value="Transmission Service">Transmission Service</option>
                        <option value="Inspection">Inspection</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="service_date" class="form-label fw-medium">Service Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="service_date" name="service_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="mileage" class="form-label fw-medium">Mileage at Service <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="mileage" name="mileage" required min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Completed" selected>Completed</option>
                        <option value="Pending">Pending</option>
                        <option value="Scheduled">Scheduled</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Service Details</h5>
            
            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <label for="description" class="form-label fw-medium">Description of Work</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="What was done?"></textarea>
                </div>
                <div class="col-md-12 mb-3">
                    <label for="parts_replaced" class="form-label fw-medium">Parts Replaced</label>
                    <textarea class="form-control" id="parts_replaced" name="parts_replaced" rows="2" placeholder="List of replaced parts"></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="service_provider" class="form-label fw-medium">Service Provider / Mechanic</label>
                    <input type="text" class="form-control" id="service_provider" name="service_provider" value="<?php echo htmlspecialchars($prefs['preferred_service_provider'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cost" class="form-label fw-medium">Cost ($) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="cost" name="cost" required min="0" step="0.01" value="0.00">
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Next Service Projection</h5>
            <div class="alert alert-info py-2 small">
                <i class="bi bi-info-circle me-1"></i> Projections help generate future maintenance reminders automatically.
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="next_service_date" class="form-label fw-medium">Next Service Date</label>
                    <?php 
                        $defaultMonths = $prefs['default_service_interval'] ?? 6;
                        $suggestedNextDate = date('Y-m-d', strtotime("+$defaultMonths months"));
                    ?>
                    <input type="date" class="form-control" id="next_service_date" name="next_service_date" value="<?php echo $suggestedNextDate; ?>">
                    <div class="form-text">Suggested based on your <?php echo $defaultMonths; ?>-month preference.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="next_service_mileage" class="form-label fw-medium">Next Service Mileage</label>
                    <?php $defaultMileage = $prefs['default_mileage_interval'] ?? 5000; ?>
                    <input type="number" class="form-control" id="next_service_mileage" name="next_service_mileage" min="0">
                    <div class="form-text">Suggest adding <?php echo number_format($defaultMileage); ?> to current mileage.</div>
                </div>
                <div class="col-md-12 mb-3">
                    <label for="remarks" class="form-label fw-medium">Additional Remarks for Next Service</label>
                    <input type="text" class="form-control" id="remarks" name="remarks" placeholder="E.g. Check brakes next time">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Record</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mileageInput = document.getElementById('mileage');
    const nextMileageInput = document.getElementById('next_service_mileage');
    const defaultInterval = <?php echo $defaultMileage; ?>;
    
    mileageInput.addEventListener('input', function() {
        if(this.value) {
            nextMileageInput.value = parseInt(this.value) + defaultInterval;
        } else {
            nextMileageInput.value = '';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
