<?php
// reports/costs.php
$pageTitle = 'Cost Report';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];
$yearFilter = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    $pdo = Database::getInstance();
    
    // Available years for filter
    $yStmt = $pdo->prepare("SELECT DISTINCT YEAR(service_date) as yr FROM service_records sr JOIN vehicles v ON sr.vehicle_id = v.id WHERE v.user_id = ? ORDER BY yr DESC");
    $yStmt->execute([$userId]);
    $years = $yStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array(date('Y'), $years)) $years[] = date('Y');
    rsort($years);
    
    // Cost by Month (for Chart and Table)
    $mStmt = $pdo->prepare("
        SELECT MONTH(service_date) as month, SUM(cost) as total
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? AND YEAR(service_date) = ?
        GROUP BY MONTH(service_date)
        ORDER BY month
    ");
    $mStmt->execute([$userId, $yearFilter]);
    $monthlyData = $mStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $chartLabels = [];
    $chartData = [];
    $tableData = [];
    $totalYearCost = 0;
    
    for ($m = 1; $m <= 12; $m++) {
        $monthName = date("F", mktime(0, 0, 0, $m, 10));
        $cost = isset($monthlyData[$m]) ? (float)$monthlyData[$m] : 0;
        
        $chartLabels[] = substr($monthName, 0, 3);
        $chartData[] = $cost;
        
        $tableData[] = [
            'month' => $monthName,
            'cost' => $cost
        ];
        $totalYearCost += $cost;
    }
    
    // Cost by Vehicle
    $vStmt = $pdo->prepare("
        SELECT v.make, v.model, v.plate_number, SUM(sr.cost) as total
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? AND YEAR(sr.service_date) = ?
        GROUP BY v.id
        ORDER BY total DESC
    ");
    $vStmt->execute([$userId, $yearFilter]);
    $vehicleCosts = $vStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Cost Report Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $years = [date('Y')];
    $tableData = [];
    $vehicleCosts = [];
    $chartLabels = [];
    $chartData = [];
    $totalYearCost = 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Cost Report</h1>
    <div class="d-flex gap-2">
        <form action="" method="GET" class="d-flex align-items-center">
            <select name="year" class="form-select form-select-sm me-2" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $yearFilter ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary shadow-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Monthly Spending - <?php echo $yearFilter; ?></h6>
            </div>
            <div class="card-body">
                <canvas id="costChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100 bg-primary text-white">
            <div class="card-body text-center d-flex flex-column justify-content-center p-5">
                <h5 class="opacity-75 mb-3 text-uppercase tracking-wider">Total Spent in <?php echo $yearFilter; ?></h5>
                <h1 class="display-3 fw-bold mb-0">$<?php echo number_format($totalYearCost, 2); ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Monthly Breakdown</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Month</th>
                            <th class="text-end pe-4">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData as $row): ?>
                            <tr>
                                <td class="ps-4 fw-medium"><?php echo $row['month']; ?></td>
                                <td class="text-end pe-4 <?php echo $row['cost'] > 0 ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                    $<?php echo number_format($row['cost'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td class="ps-4">TOTAL</td>
                            <td class="text-end pe-4">$<?php echo number_format($totalYearCost, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Cost by Vehicle</h6>
            </div>
            <div class="card-body p-0">
                <?php if(empty($vehicleCosts)): ?>
                    <div class="p-4 text-center text-muted">No costs recorded for this year.</div>
                <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Vehicle</th>
                                <th class="text-end pe-4">Total Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicleCosts as $vc): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-medium text-dark"><?php echo htmlspecialchars($vc['make'] . ' ' . $vc['model']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($vc['plate_number']); ?></div>
                                    </td>
                                    <td class="text-end pe-4 text-danger fw-bold align-middle">
                                        $<?php echo number_format($vc['total'], 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('costChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Maintenance Cost ($)',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: 'rgba(14, 165, 233, 0.7)',
                    borderColor: 'rgb(14, 165, 233)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '$' + value; }
                        }
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
    .card { position: static; width: 100%; border: none !important; box-shadow: none !important; margin-bottom: 20px;}
    .btn, .navbar, .sidebar { display: none !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
