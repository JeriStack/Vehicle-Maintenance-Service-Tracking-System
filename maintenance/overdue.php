<?php
// maintenance/overdue.php
$pageTitle = 'Overdue Maintenance';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    $stmt = $pdo->prepare("
        SELECT sr.*, v.make, v.model, v.plate_number, v.current_mileage 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? 
        AND sr.status = 'Completed'
        AND (
            (sr.next_service_date IS NOT NULL AND sr.next_service_date < CURRENT_DATE)
            OR 
            (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage < v.current_mileage)
        )
        ORDER BY sr.next_service_date ASC, sr.next_service_mileage ASC
    ");
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Maintenance Overdue Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $records = [];
    echo '<div class="alert alert-danger">Failed to load data.</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Overdue Maintenance</h1>
    <a href="<?php echo BASE_URL; ?>maintenance/index.php" class="btn btn-outline-secondary shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Overview
    </a>
</div>

<div class="card shadow-sm border-0 border-top border-danger border-3">
    <div class="card-body p-0">
        <?php if (empty($records)): ?>
            <div class="text-center p-5">
                <div class="display-1 text-muted mb-3 opacity-25"><i class="bi bi-check2-all"></i></div>
                <h4 class="text-secondary">All caught up!</h4>
                <p class="text-muted">You have no overdue maintenance records.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Vehicle</th>
                            <th>Previous Service</th>
                            <th>Reason for Alert</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($r['plate_number']); ?></div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?php echo htmlspecialchars($r['service_type']); ?></div>
                                    <div class="small text-muted">Done on <?php echo date('M d, Y', strtotime($r['service_date'])); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $reasons = [];
                                    if ($r['next_service_date'] && $r['next_service_date'] < date('Y-m-d')) {
                                        $days = (time() - strtotime($r['next_service_date'])) / (60 * 60 * 24);
                                        $reasons[] = '<span class="text-danger fw-bold"><i class="bi bi-calendar-x me-1"></i>Overdue by ' . floor($days) . ' days</span>';
                                    }
                                    if ($r['next_service_mileage'] && $r['next_service_mileage'] < $r['current_mileage']) {
                                        $diff = $r['current_mileage'] - $r['next_service_mileage'];
                                        $reasons[] = '<span class="text-danger fw-bold"><i class="bi bi-speedometer2 me-1"></i>Overdue by ' . number_format($diff) . ' km</span>';
                                    }
                                    echo implode('<br>', $reasons);
                                    ?>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="<?php echo BASE_URL; ?>services/create.php?vehicle_id=<?php echo $r['vehicle_id']; ?>" class="btn btn-sm btn-danger shadow-sm">
                                        <i class="bi bi-wrench me-1"></i> Record Service
                                    </a>
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
