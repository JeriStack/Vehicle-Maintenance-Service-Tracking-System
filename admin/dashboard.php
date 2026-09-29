<?php
// admin/dashboard.php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

try {
    $pdo = Database::getInstance();
    
    // Total Users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();
    
    // Total Vehicles
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles");
    $stmt->execute();
    $totalVehicles = $stmt->fetchColumn();

    // Total Services
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_records");
    $stmt->execute();
    $totalServices = $stmt->fetchColumn();
    
    // Completed Services
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_records WHERE status = 'Completed'");
    $stmt->execute();
    $completedServices = $stmt->fetchColumn();
    
    // Upcoming Services (Next 30 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM service_records 
        WHERE next_service_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $upcomingServices = $stmt->fetchColumn();

    // Overdue Services (next_service_date < CURRENT_DATE and status='Completed' might not make sense, let's use standard logic)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM service_records 
        WHERE next_service_date < CURRENT_DATE
    ");
    $stmt->execute();
    $overdueServices = $stmt->fetchColumn();
    
    // Total Cost
    $stmt = $pdo->prepare("SELECT SUM(cost) FROM service_records");
    $stmt->execute();
    $totalCost = $stmt->fetchColumn() ?? 0;
    
    // Recent Services
    $stmt = $pdo->prepare("
        SELECT sr.*, v.make, v.model, v.plate_number, u.full_name as owner_name 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        JOIN users u ON v.user_id = u.id
        ORDER BY sr.service_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentServices = $stmt->fetchAll();

    // Recent Users
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, created_at FROM users 
        ORDER BY created_at DESC LIMIT 5
    ");
    $stmt->execute();
    $recentUsers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load dashboard data.";
}
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Users</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $totalUsers; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Vehicles</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $totalVehicles; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-car-front"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Total Services</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $totalServices; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-tools"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Overdue Maintenance</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $overdueServices; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-secondary text-white h-100 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-uppercase fw-semibold mb-1 opacity-75">Upcoming Maintenance</h6>
                    <h2 class="mb-0 fw-bold"><?php echo $upcomingServices; ?></h2>
                </div>
                <div class="fs-1 opacity-50"><i class="bi bi-calendar-event"></i></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
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

    <div class="col-md-4">
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
                <h6 class="m-0 fw-bold text-dark">Recent System-wide Services</h6>
                <a href="<?php echo BASE_URL; ?>admin/services.php" class="btn btn-sm btn-outline-primary">View All</a>
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
                                    <th class="ps-4">Owner</th>
                                    <th>Vehicle</th>
                                    <th>Service Type</th>
                                    <th>Date</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentServices as $service): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-medium text-dark"><?php echo htmlspecialchars($service['owner_name']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><?php echo htmlspecialchars($service['make'] . ' ' . $service['model']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($service['plate_number']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($service['service_type']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($service['service_date'])); ?></td>
                                        <td>$<?php echo number_format($service['cost'], 2); ?></td>
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
                <h6 class="m-0 fw-bold text-dark">Recent Users</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentUsers)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-people fs-2 mb-2 d-block"></i>
                        No recent users.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentUsers as $usr): ?>
                            <li class="list-group-item p-3 border-bottom">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-primary">
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo htmlspecialchars($usr['full_name']); ?>
                                    </h6>
                                    <small class="text-muted"><?php echo date('M d', strtotime($usr['created_at'])); ?></small>
                                </div>
                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($usr['email']); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="p-3 text-center border-top">
                        <a href="<?php echo BASE_URL; ?>admin/users.php" class="text-decoration-none fw-medium text-primary">View All Users</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
