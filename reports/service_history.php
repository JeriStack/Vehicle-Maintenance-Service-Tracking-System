<?php
// reports/service_history.php
$pageTitle = 'Service History Report';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

// Filters
$vehicleFilter = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : '';
$typeFilter = isset($_GET['service_type']) ? $_GET['service_type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

try {
    $pdo = Database::getInstance();
    
    // Dropdowns data
    $vStmt = $pdo->prepare("SELECT id, make, model, plate_number FROM vehicles WHERE user_id = ?");
    $vStmt->execute([$userId]);
    $vehicles = $vStmt->fetchAll();
    
    $tStmt = $pdo->prepare("SELECT DISTINCT service_type FROM service_records sr JOIN vehicles v ON sr.vehicle_id = v.id WHERE v.user_id = ?");
    $tStmt->execute([$userId]);
    $types = $tStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Main query
    $query = "
        SELECT sr.*, v.make, v.model, v.plate_number 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ?
    ";
    $params = [$userId];
    
    if ($vehicleFilter) {
        $query .= " AND v.id = ?";
        $params[] = $vehicleFilter;
    }
    if ($typeFilter) {
        $query .= " AND sr.service_type = ?";
        $params[] = $typeFilter;
    }
    if ($statusFilter) {
        $query .= " AND sr.status = ?";
        $params[] = $statusFilter;
    }
    if ($dateFrom) {
        $query .= " AND sr.service_date >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $query .= " AND sr.service_date <= ?";
        $params[] = $dateTo;
    }
    
    $query .= " ORDER BY sr.service_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("History Report Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $records = [];
    $vehicles = [];
    $types = [];
}

// Build URL for CSV export
$csvUrl = BASE_URL . "reports/export_csv.php?type=service_history";
if ($vehicleFilter) $csvUrl .= "&vehicle_id=$vehicleFilter";
if ($typeFilter) $csvUrl .= "&service_type=" . urlencode($typeFilter);
if ($statusFilter) $csvUrl .= "&status=" . urlencode($statusFilter);
if ($dateFrom) $csvUrl .= "&date_from=$dateFrom";
if ($dateTo) $csvUrl .= "&date_to=$dateTo";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Service History Report</h1>
    <div>
        <a href="<?php echo $csvUrl; ?>" class="btn btn-outline-success shadow-sm me-2">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body bg-light rounded">
        <form action="" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted fw-bold">Vehicle</label>
                <select name="vehicle_id" class="form-select">
                    <option value="">All Vehicles</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo $vehicleFilter == $v['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted fw-bold">Service Type</label>
                <select name="service_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $typeFilter === $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Scheduled" <?php echo $statusFilter === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted fw-bold">Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted fw-bold">Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if(empty($records)): ?>
            <div class="p-5 text-center text-muted">No records found matching your filters.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Vehicle</th>
                            <th>Service Type</th>
                            <th>Description</th>
                            <th>Mileage</th>
                            <th>Cost</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($records as $r): ?>
                            <tr>
                                <td class="ps-4"><?php echo date('M d, Y', strtotime($r['service_date'])); ?></td>
                                <td>
                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($r['plate_number']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($r['service_type']); ?></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($r['description']); ?>">
                                        <?php echo htmlspecialchars($r['description'] ?: '-'); ?>
                                    </div>
                                </td>
                                <td><?php echo number_format($r['mileage']); ?></td>
                                <td class="fw-bold">$<?php echo number_format($r['cost'], 2); ?></td>
                                <td class="pe-4">
                                    <?php if ($r['status'] === 'Completed'): ?>
                                        <span class="text-success fw-bold">Completed</span>
                                    <?php else: ?>
                                        <span class="text-warning text-dark fw-bold"><?php echo htmlspecialchars($r['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .card:not(.no-print), .card:not(.no-print) * { visibility: visible; }
    .card:not(.no-print) { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
    .btn, .navbar, .sidebar, .no-print { display: none !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
