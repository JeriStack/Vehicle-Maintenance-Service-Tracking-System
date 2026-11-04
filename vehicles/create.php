<?php
// vehicles/create.php
$pageTitle = 'Add Vehicle';
require_once __DIR__ . '/../includes/header.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Add New Vehicle</h1>
    <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Vehicles
    </a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body p-4">
        <form action="<?php echo BASE_URL; ?>actions/vehicle_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="create">
            
            <h5 class="mb-3 text-primary border-bottom pb-2">Basic Information</h5>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label for="plate_number" class="form-label fw-medium">Plate Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-uppercase" id="plate_number" name="plate_number" required placeholder="e.g. ABC-1234">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="vehicle_type" class="form-label fw-medium">Vehicle Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                        <option value="">Select Type</option>
                        <option value="Sedan">Sedan</option>
                        <option value="SUV">SUV</option>
                        <option value="Hatchback">Hatchback</option>
                        <option value="Truck">Truck</option>
                        <option value="Van">Van</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="Active" selected>Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Sold">Sold</option>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Vehicle Details</h5>
            
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <label for="make" class="form-label fw-medium">Make (Brand) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="make" name="make" required placeholder="e.g. Toyota">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="model" class="form-label fw-medium">Model <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="model" name="model" required placeholder="e.g. Vios">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="year" class="form-label fw-medium">Year <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="year" name="year" required min="1900" max="<?php echo date('Y') + 1; ?>" value="<?php echo date('Y'); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="color" class="form-label fw-medium">Color</label>
                    <input type="text" class="form-control" id="color" name="color" placeholder="e.g. White">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="vin_number" class="form-label fw-medium">VIN / Chassis Number</label>
                    <input type="text" class="form-control text-uppercase" id="vin_number" name="vin_number" placeholder="17-character VIN">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="current_mileage" class="form-label fw-medium">Current Mileage (km/mi) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="current_mileage" name="current_mileage" required min="0" value="0">
                </div>
            </div>

            <h5 class="mb-3 text-primary border-bottom pb-2">Registration Information</h5>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <label for="registration_date" class="form-label fw-medium">Registration Date</label>
                    <input type="date" class="form-control" id="registration_date" name="registration_date">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="registration_expiry" class="form-label fw-medium">Registration Expiry Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="registration_expiry" name="registration_expiry" required>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?php echo BASE_URL; ?>vehicles/index.php" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
