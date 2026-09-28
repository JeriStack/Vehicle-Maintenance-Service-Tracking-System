<?php
// maintenance/index.php
$pageTitle = 'Maintenance Tracking';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    // Upcoming (within 30 days or 1000 km, but strictly greater than today/current)
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
    $countUpcoming = $stmtUpcoming->fetchColumn();
    
    // Due (today or exactly at mileage limit)
    $stmtDue = $pdo->prepare("
        SELECT COUNT(*) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? 
        AND sr.status = 'Completed'
        AND (
            (sr.next_service_date IS NOT NULL AND sr.next_service_date = CURRENT_DATE)
            OR 
            (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage = v.current_mileage)
        )
    ");
    $stmtDue->execute([$userId]);
    $countDue = $stmtDue->fetchColumn();
    
    // Overdue (past date or past mileage limit)
    $stmtOverdue = $pdo->prepare("
        SELECT COUNT(*) FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ? 
        AND sr.status = 'Completed'
        AND (
            (sr.next_service_date IS NOT NULL AND sr.next_service_date < CURRENT_DATE)
            OR 
            (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage < v.current_mileage)
        )
    ");
    $stmtOverdue->execute([$userId]);
    $countOverdue = $stmtOverdue->fetchColumn();

} catch (PDOException $e) {
    error_log("Maintenance Index Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $countUpcoming = $countDue = $countOverdue = 0;
    echo '<div class="alert alert-danger">Failed to load maintenance tracking data.</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Maintenance Tracking Overview</h1>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <a href="<?php echo BASE_URL; ?>maintenance/upcoming.php" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 bg-white hover-elevate">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-info mb-3"><i class="bi bi-calendar-event"></i></div>
                    <h4 class="fw-bold text-dark">Upcoming</h4>
                    <p class="text-muted">Services due within 30 days or 1,000 km</p>
                    <span class="badge bg-info rounded-pill fs-5 px-4 py-2 text-dark"><?php echo $countUpcoming; ?> Records</span>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-md-4">
        <a href="<?php echo BASE_URL; ?>maintenance/due.php" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 bg-white hover-elevate">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-warning mb-3"><i class="bi bi-exclamation-circle"></i></div>
                    <h4 class="fw-bold text-dark">Due Today</h4>
                    <p class="text-muted">Services due today or at exact mileage limit</p>
                    <span class="badge bg-warning text-dark rounded-pill fs-5 px-4 py-2"><?php echo $countDue; ?> Records</span>
                </div>
            </div>
        </a>
    </div>
    
    <div class="col-md-4">
        <a href="<?php echo BASE_URL; ?>maintenance/overdue.php" class="text-decoration-none">
            <div class="card shadow-sm border-0 h-100 bg-white hover-elevate">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-danger mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                    <h4 class="fw-bold text-dark">Overdue</h4>
                    <p class="text-muted">Services past their scheduled date or mileage</p>
                    <span class="badge bg-danger rounded-pill fs-5 px-4 py-2"><?php echo $countOverdue; ?> Records</span>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
.hover-elevate {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-elevate:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
