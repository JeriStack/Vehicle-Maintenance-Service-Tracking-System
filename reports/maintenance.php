<?php
// reports/maintenance.php
$pageTitle = 'Maintenance Summary';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    // Total services & costs
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_services,
            SUM(CASE WHEN sr.status = 'Completed' THEN 1 ELSE 0 END) as completed_services,
            SUM(cost) as total_cost
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ?
    ");
    $stmt->execute([$userId]);
    $totals = $stmt->fetch();
    
    // Ensure nulls from SUM() fallback to 0
    $totals['completed_services'] = $totals['completed_services'] ?? 0;
    $totals['total_cost'] = $totals['total_cost'] ?? 0;
    
    // Upcoming
    $stmtUpcoming = $pdo->prepare("
        SELECT COUNT(*) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? 
        AND sr.status = 'Completed'
        AND (
            (sr.next_service_date IS NOT NULL AND sr.next_service_date > CURRENT_DATE AND sr.next_service_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))
            OR 
            (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage > v.current_mileage AND sr.next_service_mileage <= v.current_mileage + 1000)
        )
    ");
    $stmtUpcoming->execute([$userId]);
    $upcoming = $stmtUpcoming->fetchColumn();
    
    // Overdue & Due
    $stmtOverdue = $pdo->prepare("
        SELECT COUNT(*) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? 
        AND sr.status = 'Completed'
        AND (
            (sr.next_service_date IS NOT NULL AND sr.next_service_date <= CURRENT_DATE)
            OR 
            (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage <= v.current_mileage)
        )
    ");
    $stmtOverdue->execute([$userId]);
    $overdue = $stmtOverdue->fetchColumn();

} catch (PDOException $e) {
    error_log("Report Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $totals = ['total_services' => 0, 'completed_services' => 0, 'total_cost' => 0];
    $upcoming = 0;
    $overdue = 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Maintenance Summary</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>reports/export_csv.php?type=maintenance_summary" class="btn btn-outline-success shadow-sm me-2">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Overall Statistics</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <td class="text-muted fw-medium py-3 border-bottom">Total Services Recorded</td>
                            <td class="text-end fw-bold py-3 border-bottom fs-5"><?php echo number_format($totals['total_services']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium py-3 border-bottom">Completed Services</td>
                            <td class="text-end fw-bold py-3 border-bottom fs-5 text-success"><?php echo number_format($totals['completed_services']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium py-3 border-bottom">Upcoming Services (30 Days)</td>
                            <td class="text-end fw-bold py-3 border-bottom fs-5 text-info"><?php echo number_format($upcoming); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-medium py-3 border-bottom">Due & Overdue Services</td>
                            <td class="text-end fw-bold py-3 border-bottom fs-5 text-danger"><?php echo number_format($overdue); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold py-3">Total Maintenance Cost</td>
                            <td class="text-end fw-bold py-3 fs-4 text-primary">$<?php echo number_format($totals['total_cost'] ?? 0, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Service Status Distribution</h6>
            </div>
            <div class="card-body d-flex justify-content-center align-items-center">
                <canvas id="statusChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('statusChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Upcoming', 'Due/Overdue'],
                datasets: [{
                    data: [
                        <?php echo $totals['completed_services']; ?>, 
                        <?php echo $upcoming; ?>, 
                        <?php echo $overdue; ?>
                    ],
                    backgroundColor: ['#10b981', '#0ea5e9', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true }
                    }
                }
            }
        });
    }
});
</script>

<style>
@media print {
    body * { visibility: hidden; }
    .card, .card * { visibility: visible; }
    .card { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
    .btn, .navbar, .sidebar { display: none !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
