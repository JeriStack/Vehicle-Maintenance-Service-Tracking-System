<?php
// actions/vehicle_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/websocket_helper.php';

use VMS\Core\Csrf;

// Protect action route
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "vehicles/index.php");
    exit;
}

// CSRF Protection
if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "vehicles/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();

    if ($action === 'create') {
        $plateNumber = strtoupper(trim((string) ($_POST['plate_number'] ?? '')));
        $plateNumberNormalized = strtoupper(str_replace([' ', '-'], '', $plateNumber));
        $vehicleType = trim((string) ($_POST['vehicle_type'] ?? ''));
        $make = trim((string) ($_POST['make'] ?? ''));
        $model = trim((string) ($_POST['model'] ?? ''));
        $year = (int) ($_POST['year'] ?? 0);
        $color = trim((string) ($_POST['color'] ?? ''));
        $vinNumber = strtoupper(trim((string) ($_POST['vin_number'] ?? '')));
        $currentMileage = (int) ($_POST['current_mileage'] ?? 0);
        $registrationDate = !empty($_POST['registration_date']) ? $_POST['registration_date'] : null;
        $registrationExpiry = !empty($_POST['registration_expiry']) ? $_POST['registration_expiry'] : null;
        $status = trim((string) ($_POST['status'] ?? 'active'));

        // Basic validation
        if (empty($plateNumber) || empty($plateNumberNormalized) || empty($make) || empty($model) || empty($year) || empty($registrationExpiry)) {
            $_SESSION['flash_error'] = "Please fill in all required fields.";
            header("Location: " . BASE_URL . "vehicles/create.php");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                user_id, plate_number, plate_number_normalized, vehicle_type, make, model, year, color, 
                vin_number, current_mileage, registration_date, registration_expiry, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId, $plateNumber, $plateNumberNormalized, $vehicleType, $make, $model, $year, $color, 
            $vinNumber, $currentMileage, $registrationDate, $registrationExpiry, $status
        ]);
        
        $vehicleId = $pdo->lastInsertId();
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Added vehicle', 'vehicles', $vehicleId]);
        
        WebSocketHelper::vehicleUpdated($userId, [
            'id'           => $vehicleId,
            'plate_number' => $plateNumber,
            'make'         => $make,
            'model'        => $model,
            'action'       => 'create'
        ]);

        $_SESSION['flash_success'] = "Vehicle added successfully!";
        header("Location: " . BASE_URL . "vehicles/index.php");
        exit;
    } elseif ($action === 'update') {
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);
        $plateNumber = strtoupper(trim((string) ($_POST['plate_number'] ?? '')));
        $plateNumberNormalized = strtoupper(str_replace([' ', '-'], '', $plateNumber));
        $vehicleType = trim((string) ($_POST['vehicle_type'] ?? ''));
        $make = trim((string) ($_POST['make'] ?? ''));
        $model = trim((string) ($_POST['model'] ?? ''));
        $year = (int) ($_POST['year'] ?? 0);
        $color = trim((string) ($_POST['color'] ?? ''));
        $vinNumber = strtoupper(trim((string) ($_POST['vin_number'] ?? '')));
        $currentMileage = (int) ($_POST['current_mileage'] ?? 0);
        $registrationDate = !empty($_POST['registration_date']) ? $_POST['registration_date'] : null;
        $registrationExpiry = !empty($_POST['registration_expiry']) ? $_POST['registration_expiry'] : null;
        $status = trim((string) ($_POST['status'] ?? 'active'));

        // Basic validation
        if (empty($plateNumber) || empty($plateNumberNormalized) || empty($make) || empty($model) || empty($year) || empty($registrationExpiry) || empty($vehicleId)) {
            $_SESSION['flash_error'] = "Please fill in all required fields.";
            header("Location: " . BASE_URL . "vehicles/edit.php?id=" . $vehicleId);
            exit;
        }

        // Verify ownership
        $checkStmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$vehicleId, $userId]);
        if (!$checkStmt->fetch()) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "vehicles/index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE vehicles SET 
                plate_number = ?, plate_number_normalized = ?, vehicle_type = ?, make = ?, model = ?, year = ?, color = ?, 
                vin_number = ?, current_mileage = ?, registration_date = ?, registration_expiry = ?, status = ?
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $plateNumber, $plateNumberNormalized, $vehicleType, $make, $model, $year, $color, 
            $vinNumber, $currentMileage, $registrationDate, $registrationExpiry, $status,
            $vehicleId, $userId
        ]);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Updated vehicle', 'vehicles', $vehicleId]);
        
        WebSocketHelper::vehicleUpdated($userId, [
            'id'           => $vehicleId,
            'plate_number' => $plateNumber,
            'action'       => 'update'
        ]);

        $_SESSION['flash_success'] = "Vehicle updated successfully!";
        header("Location: " . BASE_URL . "vehicles/index.php");
        exit;

    } elseif ($action === 'delete') {
        $vehicleId = (int) $_POST['vehicle_id'];

        // Verify ownership
        $checkStmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$vehicleId, $userId]);
        if (!$checkStmt->fetch()) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "vehicles/index.php");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ? AND user_id = ?");
        $stmt->execute([$vehicleId, $userId]);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Deleted vehicle', 'vehicles', $vehicleId]);
        
        WebSocketHelper::vehicleUpdated($userId, [
            'id'     => $vehicleId,
            'action' => 'delete'
        ]);

        $_SESSION['flash_success'] = "Vehicle deleted successfully!";
        header("Location: " . BASE_URL . "vehicles/index.php");
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Vehicle Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred. Please try again.";
    
    if ($action === 'create') {
        header("Location: " . BASE_URL . "vehicles/create.php");
    } else {
        header("Location: " . BASE_URL . "vehicles/index.php");
    }
    exit;
}
