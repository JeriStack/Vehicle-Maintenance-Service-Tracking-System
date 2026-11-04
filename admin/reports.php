<?php
// admin/reports.php
$pageTitle = 'System Reports';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$reportType = $_GET['type'] ?? 'summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

try {
    $pdo = Database::getInstance();
    
    if ($reportType === 'summary') {
        // High level summary
        $stats = [];
        $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['vehicles'] = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
        $stats['services'] = $pdo->query("SELECT COUNT(*) FROM service_records")->fetchColumn();
        $stats['total_cost'] = $pdo->query("SELECT SUM(cost) FROM service_records")->fetchColumn() ?? 0;
        
    } elseif ($reportType === 'monthly_cost') {
        // Monthly Cost Report
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(service_date, '%Y-%m') as month, SUM(cost) as total_cost, COUNT(id) as service_count
            FROM service_records
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute();
        $monthlyData = $stmt->fetchAll();
    } elseif ($reportType === 'services_date_range') {
        // Services within date range
        $stmt = $pdo->prepare("
            SELECT sr.*, v.plate_number, u.full_name as owner_name 
            FROM service_records sr
            JOIN vehicles v ON sr.vehicle_id = v.id
            JOIN users u ON v.user_id = u.id
            WHERE sr.service_date BETWEEN ? AND ?
            ORDER BY sr.service_date DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $rangeServices = $stmt->fetchAll();
        
        $totalCost = array_sum(array_column($rangeServices, 'cost'));
    }
    
} catch (PDOException $e) {
    error_log("Admin Reports Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to generate report.";
}
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Report Types</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="?type=summary" class="list-group-item list-group-item-action <?php echo $reportType === 'summary' ? 'active' : ''; ?>">
                    <i class="bi bi-pie-chart me-2"></i> System Summary
                </a>
                <a href="?type=monthly_cost" class="list-group-item list-group-item-action <?php echo $reportType === 'monthly_cost' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart me-2"></i> Monthly Costs
                </a>
                <a href="?type=services_date_range" class="list-group-item list-group-item-action <?php echo $reportType === 'services_date_range' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-range me-2"></i> Services by Date
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">
                    <?php 
                    if ($reportType === 'summary') echo 'System Summary Report';
                    elseif ($reportType === 'monthly_cost') echo 'Monthly Maintenance Cost Report';
                    elseif ($reportType === 'services_date_range') echo 'Services Date Range Report';
                    ?>
                </h6>
            </div>
            <div class="card-body">
                
                <?php if ($reportType === 'summary'): ?>
                    <div class="row g-4 text-center">
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="fw-bold text-primary mb-1"><?php echo $stats['users']; ?></h3>
                                <div class="text-muted small text-uppercase">Total Users</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="fw-bold text-info mb-1"><?php echo $stats['vehicles']; ?></h3>
                                <div class="text-muted small text-uppercase">Total Vehicles</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="fw-bold text-success mb-1"><?php echo $stats['services']; ?></h3>
                                <div class="text-muted small text-uppercase">Total Services</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <h3 class="fw-bold text-warning mb-1">$<?php echo number_format($stats['total_cost'], 2); ?></h3>
                                <div class="text-muted small text-uppercase">Total Cost</div>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($reportType === 'monthly_cost'): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th>Total Services</th>
                                    <th>Total Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($monthlyData)): ?>
                                    <?php foreach ($monthlyData as $row): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                                            <td><?php echo $row['service_count']; ?></td>
                                            <td>$<?php echo number_format($row['total_cost'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">No data available.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($reportType === 'services_date_range'): ?>
                    <form action="" method="GET" class="row g-3 align-items-end mb-4">
                        <input type="hidden" name="type" value="services_date_range">
                        <div class="col-md-4">
                            <label class="form-label small">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                    
                    <div class="alert alert-info py-2">
                        <strong>Total Cost for Period:</strong> $<?php echo number_format($totalCost, 2); ?> 
                        (<?php echo count($rangeServices); ?> services)
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover border">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Owner</th>
                                    <th>Plate</th>
                                    <th>Service</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($rangeServices)): ?>
                                    <?php foreach ($rangeServices as $s): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($s['service_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($s['owner_name']); ?></td>
                                            <td><?php echo htmlspecialchars($s['plate_number']); ?></td>
                                            <td><?php echo htmlspecialchars($s['service_type']); ?></td>
                                            <td>$<?php echo number_format($s['cost'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No services found in this date range.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
