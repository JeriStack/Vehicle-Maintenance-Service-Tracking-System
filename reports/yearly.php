<?php
// reports/yearly.php
$pageTitle = 'Yearly Summary Report';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    $stmt = $pdo->prepare("
        SELECT 
            YEAR(service_date) as year, 
            COUNT(sr.id) as service_count,
            SUM(cost) as total_cost,
            COUNT(DISTINCT sr.vehicle_id) as vehicles_serviced
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? 
        GROUP BY YEAR(service_date)
        ORDER BY year DESC
    ");
    $stmt->execute([$userId]);
    $yearlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Yearly Report Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $yearlyData = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Yearly Summary</h1>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary shadow-sm">
        <i class="bi bi-printer me-1"></i> Print
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <?php if(empty($yearlyData)): ?>
            <div class="p-5 text-center text-muted">No yearly data available.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Year</th>
                            <th class="text-center">Vehicles Serviced</th>
                            <th class="text-center">Total Services</th>
                            <th class="text-end pe-4">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($yearlyData as $row): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark fs-5"><?php echo $row['year']; ?></td>
                                <td class="text-center"><?php echo $row['vehicles_serviced']; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?php echo $row['service_count']; ?></span>
                                </td>
                                <td class="text-end pe-4 text-danger fw-bold fs-5">
                                    $<?php echo number_format($row['total_cost'], 2); ?>
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
