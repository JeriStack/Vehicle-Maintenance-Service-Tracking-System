<?php
// reports/export_csv.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';

try {
    $pdo = Database::getInstance();
    
    if ($type === 'maintenance_summary') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=maintenance_summary_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Vehicle Make', 'Vehicle Model', 'Plate Number', 'Total Services', 'Completed', 'Total Cost', 'Upcoming', 'Due/Overdue']);
        
        $stmt = $pdo->prepare("
            SELECT 
                v.make, v.model, v.plate_number,
                COUNT(sr.id) as total_services,
                SUM(CASE WHEN sr.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(sr.cost) as total_cost,
                SUM(CASE WHEN sr.status = 'Completed' AND ((sr.next_service_date IS NOT NULL AND sr.next_service_date > CURRENT_DATE AND sr.next_service_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)) OR (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage > v.current_mileage AND sr.next_service_mileage <= v.current_mileage + 1000)) THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN sr.status = 'Completed' AND ((sr.next_service_date IS NOT NULL AND sr.next_service_date <= CURRENT_DATE) OR (sr.next_service_mileage IS NOT NULL AND sr.next_service_mileage <= v.current_mileage)) THEN 1 ELSE 0 END) as overdue
            FROM vehicles v
            LEFT JOIN service_records sr ON v.id = sr.vehicle_id
            WHERE v.user_id = ?
            GROUP BY v.id
        ");
        $stmt->execute([$userId]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['make'], $row['model'], $row['plate_number'], 
                $row['total_services'], $row['completed'], $row['total_cost'] ?: '0.00',
                $row['upcoming'], $row['overdue']
            ]);
        }
        fclose($output);
        exit;
        
    } elseif ($type === 'service_history') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=service_history_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Vehicle', 'Plate Number', 'Service Type', 'Mileage', 'Cost', 'Provider', 'Description', 'Status']);
        
        $vehicleFilter = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : '';
        $typeFilter = isset($_GET['service_type']) ? $_GET['service_type'] : '';
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

        $query = "SELECT sr.*, v.make, v.model, v.plate_number FROM service_records sr JOIN vehicles v ON sr.vehicle_id = v.id WHERE v.user_id = ?";
        $params = [$userId];
        
        if ($vehicleFilter) { $query .= " AND v.id = ?"; $params[] = $vehicleFilter; }
        if ($typeFilter) { $query .= " AND sr.service_type = ?"; $params[] = $typeFilter; }
        if ($statusFilter) { $query .= " AND sr.status = ?"; $params[] = $statusFilter; }
        if ($dateFrom) { $query .= " AND sr.service_date >= ?"; $params[] = $dateFrom; }
        if ($dateTo) { $query .= " AND sr.service_date <= ?"; $params[] = $dateTo; }
        
        $query .= " ORDER BY sr.service_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['service_date'], 
                $row['make'] . ' ' . $row['model'], 
                $row['plate_number'],
                $row['service_type'], 
                $row['mileage'], 
                $row['cost'], 
                $row['service_provider'], 
                $row['description'], 
                $row['status']
            ]);
        }
        fclose($output);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("CSV Export Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    die("An error occurred generating the CSV.");
}

header("Location: " . BASE_URL . "reports/index.php");
exit;
