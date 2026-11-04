<?php
// vehicles/edit.php
$pageTitle = 'Edit Vehicle';
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
    error_log("Edit Vehicle Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred.";
    header("Location: " . BASE_URL . "vehicles/index.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Vehicle</h1>
    <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Vehicles
    </a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-4">
        <form action="<?php echo BASE_URL; ?>actions/vehicle_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
            
            <h5 class="mb-3 text-primary border-bottom pb-2">Basic Information</h5>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="plate_number" class="form-label fw-medium">Plate Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="plate_number" name="plate_number" required value="<?php echo htmlspecialchars($vehicle['plate_number']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="vehicle_type" class="form-label fw-medium">Vehicle Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                        <?php 
                        $types = ['Sedan', 'SUV', 'Hatchback', 'Truck', 'Van', 'Motorcycle', 'Other'];
                        foreach ($types as $type) {
                            $selected = ($vehicle['vehicle_type'] === $type) ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Active" <?php echo $vehicle['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $vehicle['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Sold" <?php echo $vehicle['status'] === 'Sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Vehicle Details</h5>
            
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <label for="make" class="form-label fw-medium">Make (Brand) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="make" name="make" required value="<?php echo htmlspecialchars($vehicle['make']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="model" class="form-label fw-medium">Model <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="model" name="model" required value="<?php echo htmlspecialchars($vehicle['model']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="year" class="form-label fw-medium">Year <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="year" name="year" required min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($vehicle['year']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="color" class="form-label fw-medium">Color</label>
                    <input type="text" class="form-control" id="color" name="color" value="<?php echo htmlspecialchars($vehicle['color']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="vin_number" class="form-label fw-medium">VIN / Chassis Number</label>
                    <input type="text" class="form-control text-uppercase" id="vin_number" name="vin_number" value="<?php echo htmlspecialchars($vehicle['vin_number']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="current_mileage" class="form-label fw-medium">Current Mileage (km/mi) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="current_mileage" name="current_mileage" required min="0" value="<?php echo htmlspecialchars($vehicle['current_mileage']); ?>">
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Registration Information</h5>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="registration_date" class="form-label fw-medium">Registration Date</label>
                    <input type="date" class="form-control" id="registration_date" name="registration_date" value="<?php echo htmlspecialchars($vehicle['registration_date']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="registration_expiry" class="form-label fw-medium">Registration Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="registration_expiry" name="registration_expiry" required value="<?php echo htmlspecialchars($vehicle['registration_expiry']); ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Update Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
