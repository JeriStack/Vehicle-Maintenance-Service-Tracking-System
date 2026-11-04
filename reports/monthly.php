<?php
// reports/monthly.php
$pageTitle = 'Monthly Summary Report';
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
    
    $mStmt = $pdo->prepare("
        SELECT 
            MONTH(service_date) as month, 
            COUNT(sr.id) as service_count,
            SUM(cost) as total_cost
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? AND YEAR(service_date) = ?
        GROUP BY MONTH(service_date)
        ORDER BY month
    ");
    $mStmt->execute([$userId, $yearFilter]);
    $monthlyData = $mStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process data for all 12 months
    $reportData = [];
    $totalCount = 0;
    $totalCost = 0;
    
    $mappedData = [];
    foreach ($monthlyData as $row) {
        $mappedData[$row['month']] = $row;
    }
    
    for ($m = 1; $m <= 12; $m++) {
        $count = isset($mappedData[$m]) ? $mappedData[$m]['service_count'] : 0;
        $cost = isset($mappedData[$m]) ? $mappedData[$m]['total_cost'] : 0;
        
        $reportData[] = [
            'month' => date("F", mktime(0, 0, 0, $m, 10)),
            'count' => $count,
            'cost' => $cost
        ];
        
        $totalCount += $count;
        $totalCost += $cost;
    }

} catch (PDOException $e) {
    error_log("Monthly Report Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $reportData = [];
    $years = [date('Y')];
    $totalCount = $totalCost = 0;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Monthly Summary</h1>
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Month</th>
                        <th class="text-center">Total Services</th>
                        <th class="text-end pe-4">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td class="ps-4 fw-medium"><?php echo $row['month']; ?></td>
                            <td class="text-center">
                                <?php if ($row['count'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $row['count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4 <?php echo $row['cost'] > 0 ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                $<?php echo number_format($row['cost'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-dark text-white fw-bold">
                        <td class="ps-4">TOTAL FOR YEAR</td>
                        <td class="text-center"><?php echo number_format($totalCount); ?></td>
                        <td class="text-end pe-4">$<?php echo number_format($totalCost, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
