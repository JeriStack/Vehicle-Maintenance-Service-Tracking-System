<?php
// admin/maintenance.php
$pageTitle = 'System Maintenance Monitoring';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

try {
    $pdo = Database::getInstance();
    
    // Filters
    $statusFilter = $_GET['status'] ?? '';
    $userFilter = $_GET['user'] ?? '';
    
    // We fetch all records that have a next_service_date or next_service_mileage
    $query = "
        SELECT sr.*, v.make, v.model, v.plate_number, v.current_mileage, u.full_name as owner_name, u.id as owner_id
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        JOIN users u ON v.user_id = u.id
        WHERE sr.status = 'Completed' AND (sr.next_service_date IS NOT NULL OR sr.next_service_mileage IS NOT NULL)
    ";
    
    $params = [];
    
    if (!empty($userFilter)) {
        $query .= " AND u.full_name LIKE ?";
        $params[] = "%$userFilter%";
    }
    
    // We pull them all and categorize in PHP to reuse logic and filter correctly
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $allRecords = $stmt->fetchAll();
    
    $records = [];
    $today = new DateTime();
    $today->setTime(0,0,0);
    
    $countUpcoming = 0;
    $countDue = 0;
    $countOverdue = 0;
    
    foreach ($allRecords as $r) {
        $category = 'Upcoming';
        $isOverdue = false;
        $isDue = false;
        $isUpcoming = false;
        
        if (!empty($r['next_service_date'])) {
            $nextDate = new DateTime($r['next_service_date']);
            $nextDate->setTime(0,0,0);
            $diff = $today->diff($nextDate)->days;
            $invert = $today->diff($nextDate)->invert; // 1 if nextDate < today
            
            if ($invert) {
                $isOverdue = true;
            } elseif ($diff == 0) {
                $isDue = true;
            } elseif ($diff <= 30 && !$invert) {
                $isUpcoming = true;
            }
        }
        
        if (!empty($r['next_service_mileage']) && !empty($r['current_mileage'])) {
            $diffMiles = $r['next_service_mileage'] - $r['current_mileage'];
            
            if ($diffMiles < 0) {
                $isOverdue = true;
            } elseif ($diffMiles == 0) {
                $isDue = true;
            } elseif ($diffMiles <= 1000 && $diffMiles > 0) {
                $isUpcoming = true;
            }
        }
        
        if ($isOverdue) {
            $category = 'Overdue';
            $countOverdue++;
        } elseif ($isDue) {
            $category = 'Due';
            $countDue++;
        } elseif ($isUpcoming) {
            $category = 'Upcoming';
            $countUpcoming++;
        } else {
            $category = 'Future';
        }
        
        if ($statusFilter && $statusFilter !== $category) {
            continue;
        }
        
        $r['maintenance_category'] = $category;
        $records[] = $r;
    }
    
    // Sort records: Overdue first, then Due, then Upcoming, then Future
    usort($records, function($a, $b) {
        $order = ['Overdue' => 1, 'Due' => 2, 'Upcoming' => 3, 'Future' => 4];
        if ($order[$a['maintenance_category']] == $order[$b['maintenance_category']]) {
            return strtotime($a['next_service_date']) - strtotime($b['next_service_date']);
        }
        return $order[$a['maintenance_category']] - $order[$b['maintenance_category']];
    });

} catch (PDOException $e) {
    error_log("Admin Maintenance Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load maintenance tracking data.";
    $records = [];
    $countUpcoming = $countDue = $countOverdue = 0;
}
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body p-3 text-center">
                <h6 class="fw-bold text-info mb-1"><i class="bi bi-calendar-event"></i> Upcoming</h6>
                <h3 class="mb-0 fw-bold"><?php echo $countUpcoming; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body p-3 text-center">
                <h6 class="fw-bold text-warning mb-1"><i class="bi bi-exclamation-circle text-warning"></i> Due Today</h6>
                <h3 class="mb-0 fw-bold"><?php echo $countDue; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body p-3 text-center">
                <h6 class="fw-bold text-danger mb-1"><i class="bi bi-exclamation-triangle"></i> Overdue</h6>
                <h3 class="mb-0 fw-bold"><?php echo $countOverdue; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h6 class="m-0 fw-bold text-dark">Maintenance Records</h6>
            </div>
            <div class="col-md-8">
                <form action="" method="GET" class="d-flex justify-content-md-end">
                    <select name="status" class="form-select form-select-sm me-2" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="Overdue" <?php echo $statusFilter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                        <option value="Due" <?php echo $statusFilter === 'Due' ? 'selected' : ''; ?>>Due Today</option>
                        <option value="Upcoming" <?php echo $statusFilter === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="Future" <?php echo $statusFilter === 'Future' ? 'selected' : ''; ?>>Future</option>
                    </select>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" name="user" class="form-control" placeholder="Search user..." value="<?php echo htmlspecialchars($userFilter); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <?php if (!empty($userFilter) || !empty($statusFilter)): ?>
                        <a href="maintenance.php" class="btn btn-sm btn-light ms-2" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
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
                        <th>Previous Service</th>
                        <th>Next Due Date</th>
                        <th>Next Due Mileage</th>
                        <th class="pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No maintenance records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark">
                                    <a href="user_view.php?id=<?php echo $r['owner_id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($r['owner_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($r['plate_number']); ?></div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['service_type']); ?>
                                    <div class="small text-muted">on <?php echo date('M d, Y', strtotime($r['service_date'])); ?></div>
                                </td>
                                <td>
                                    <?php echo !empty($r['next_service_date']) ? date('M d, Y', strtotime($r['next_service_date'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <?php echo !empty($r['next_service_mileage']) ? number_format($r['next_service_mileage']) . ' km' : 'N/A'; ?>
                                    <div class="small text-muted">Current: <?php echo number_format($r['current_mileage']); ?></div>
                                </td>
                                <td class="pe-4">
                                    <?php if ($r['maintenance_category'] === 'Overdue'): ?>
                                        <span class="badge bg-danger rounded-pill px-2">Overdue</span>
                                    <?php elseif ($r['maintenance_category'] === 'Due'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2">Due Today</span>
                                    <?php elseif ($r['maintenance_category'] === 'Upcoming'): ?>
                                        <span class="badge bg-info rounded-pill px-2">Upcoming</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border rounded-pill px-2">Future</span>
                                    <?php endif; ?>
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
