<?php
// dashboard/index.php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

try {
    $pdo = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // Total Vehicles
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalVehicles = $stmt->fetchColumn();
    
    // Completed Services
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? AND sr.status = 'Completed'
    ");
    $stmt->execute([$userId]);
    $completedServices = $stmt->fetchColumn();
    
    // Upcoming Services (Next 30 days based on next_service_date)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? AND sr.next_service_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    $stmt->execute([$userId]);
    $upcomingServices = $stmt->fetchColumn();
    
    // Total Cost
    $stmt = $pdo->prepare("
        SELECT SUM(cost) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalCost = $stmt->fetchColumn() ?? 0;
    
    // Recent Services
    $stmt = $pdo->prepare("
        SELECT sr.*, v.make, v.model, v.plate_number 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ?
        ORDER BY sr.service_date DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentServices = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load dashboard data.";
}
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Vehicles</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $totalVehicles; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-car-front"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Upcoming Services</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $upcomingServices; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-calendar-event"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Completed Services</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $completedServices; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Cost</h6>
                    <h2 class="mb-0 fw-bold">$<?php echo number_format($totalCost, 2); ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-currency-dollar"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">Recent Service Records</h6>
                <a href="<?php echo BASE_URL; ?>services/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentServices)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                        No recent services found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Vehicle</th>
                                    <th>Service Type</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th class="pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentServices as $service): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-medium text-dark"><?php echo htmlspecialchars($service['make'] . ' ' . $service['model']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($service['plate_number']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($service['service_type']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($service['service_date'])); ?></td>
                                        <td>$<?php echo number_format($service['cost'], 2); ?></td>
                                        <td class="pe-4">
                                            <?php if ($service['status'] === 'Completed'): ?>
                                                <span class="badge bg-success rounded-pill px-3">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-3"><?php echo htmlspecialchars($service['status']); ?></span>
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
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Upcoming Reminders</h6>
            </div>
            <div class="card-body p-0">
                <?php
                // Fetch unread notifications
                $notifs = [];
                if (isset($pdo)) {
                    $nstmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                    $nstmt->execute([$_SESSION['user_id']]);
                    $notifs = $nstmt->fetchAll();
                }
                
                if (empty($notifs)):
                ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-bell-slash fs-2 mb-2 d-block"></i>
                        No reminders at this time.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($notifs as $n): ?>
                            <li class="list-group-item p-3 border-bottom <?php echo $n['is_read'] ? '' : 'bg-light'; ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-primary">
                                        <?php if ($n['type'] === 'OVERDUE'): ?>
                                            <i class="bi bi-exclamation-circle text-danger me-1"></i>
                                        <?php elseif ($n['type'] === 'REMINDER'): ?>
                                            <i class="bi bi-calendar-event text-warning me-1"></i>
                                        <?php else: ?>
                                            <i class="bi bi-info-circle text-info me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($n['title']); ?>
                                    </h6>
                                    <small class="text-muted"><?php echo date('M d', strtotime($n['created_at'])); ?></small>
                                </div>
                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($n['message']); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="p-3 text-center border-top">
                        <a href="<?php echo BASE_URL; ?>notifications/index.php" class="text-decoration-none fw-medium text-primary">View All Notifications</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
