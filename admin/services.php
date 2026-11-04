<?php
// admin/services.php
$pageTitle = 'All Service Records';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

try {
    $pdo = Database::getInstance();
    
    // Search and Filter
    $search = $_GET['search'] ?? '';
    
    $query = "
        SELECT sr.*, v.make, v.model, v.plate_number, u.full_name as owner_name, u.id as owner_id
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        JOIN users u ON v.user_id = u.id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (v.plate_number LIKE ? OR sr.service_type LIKE ? OR u.full_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $query .= " ORDER BY sr.service_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $services = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin Services Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load services.";
    $services = [];
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h6 class="m-0 fw-bold text-dark">System-wide Services</h6>
            </div>
            <div class="col-md-8">
                <form action="" method="GET" class="d-flex justify-content-md-end">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Search owner, plate, service..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <?php if (!empty($search)): ?>
                        <a href="services.php" class="btn btn-sm btn-light ms-2" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Owner</th>
                        <th>Vehicle</th>
                        <th>Service Type</th>
                        <th>Date</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th class="pe-4">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No service records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark">
                                    <a href="user_view.php?id=<?php echo $s['owner_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($s['owner_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($s['make'] . ' ' . $s['model']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($s['plate_number']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($s['service_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($s['service_date'])); ?></td>
                                <td>$<?php echo number_format($s['cost'], 2); ?></td>
                                <td>
                                    <?php if ($s['status'] === 'Completed'): ?>
                                        <span class="badge bg-success rounded-pill px-2">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2"><?php echo htmlspecialchars($s['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4">
                                    <span class="small text-muted"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
