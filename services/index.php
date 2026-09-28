<?php
// services/index.php
$pageTitle = 'Service Records';
require_once __DIR__ . '/../includes/header.php';

try {
    $pdo = Database::getInstance();
    $userId = $_SESSION['user_id'];
    $vehicleIdFilter = isset($_GET['vehicle_id']) && is_numeric($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
    
    // Fetch all user's vehicles for the filter dropdown
    $vStmt = $pdo->prepare("SELECT id, make, model, plate_number FROM vehicles WHERE user_id = ? ORDER BY make, model");
    $vStmt->execute([$userId]);
    $userVehicles = $vStmt->fetchAll();
    
    // Build query for service records
    $query = "
        SELECT sr.*, v.make, v.model, v.plate_number 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ?
    ";
    $params = [$userId];
    
    if ($vehicleIdFilter) {
        $query .= " AND v.id = ?";
        $params[] = $vehicleIdFilter;
    }
    
    $query .= " ORDER BY sr.service_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $services = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Services Index Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $services = [];
    $userVehicles = [];
    echo '<div class="alert alert-danger">Failed to load service records.</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Service Records</h1>
    <a href="<?php echo BASE_URL; ?>services/create.php<?php echo $vehicleIdFilter ? '?vehicle_id=' . $vehicleIdFilter : ''; ?>" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Service Record
    </a>
</div>

<!-- Filter Box -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="<?php echo BASE_URL; ?>services/index.php" method="GET" class="row align-items-center">
            <div class="col-auto">
                <label for="vehicle_id" class="col-form-label fw-medium text-muted">Filter by Vehicle:</label>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="vehicle_id" name="vehicle_id" onchange="this.form.submit()">
                    <option value="">All Vehicles</option>
                    <?php foreach ($userVehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo $vehicleIdFilter === $v['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['plate_number'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($vehicleIdFilter): ?>
                <div class="col-auto">
                    <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-outline-secondary btn-sm">Clear Filter</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($services)): ?>
            <div class="text-center p-5">
                <div class="display-1 text-muted mb-3 opacity-25"><i class="bi bi-tools"></i></div>
                <h4 class="text-secondary">No service records found</h4>
                <p class="text-muted">You haven't added any service records yet.</p>
                <a href="<?php echo BASE_URL; ?>services/create.php" class="btn btn-primary mt-2">Add Your First Service</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Vehicle</th>
                            <th>Service Type</th>
                            <th>Provider</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $srv): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-medium"><?php echo date('M d, Y', strtotime($srv['service_date'])); ?></div>
                                    <div class="small text-muted">at <?php echo number_format($srv['mileage']); ?> km</div>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($srv['make'] . ' ' . $srv['model']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($srv['plate_number']); ?></div>
                                </td>
                                <td><span class="fw-medium"><?php echo htmlspecialchars($srv['service_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($srv['service_provider'] ?: '-'); ?></td>
                                <td class="fw-medium text-dark">$<?php echo number_format($srv['cost'], 2); ?></td>
                                <td>
                                    <?php if ($srv['status'] === 'Completed'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 border border-success border-opacity-25 rounded-pill">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-2 py-1 border border-warning border-opacity-25 rounded-pill text-dark"><?php echo htmlspecialchars($srv['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo BASE_URL; ?>services/view.php?id=<?php echo $srv['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="<?php echo BASE_URL; ?>services/edit.php?id=<?php echo $srv['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="<?php echo BASE_URL; ?>services/delete.php?id=<?php echo $srv['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
