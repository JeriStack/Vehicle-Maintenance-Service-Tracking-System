<?php
// actions/service_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Protect action route
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();

    if ($action === 'create') {
        $vehicleId = (int) $_POST['vehicle_id'];
        
        // Verify user owns this vehicle
        $vCheckStmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
        $vCheckStmt->execute([$vehicleId, $userId]);
        if (!$vCheckStmt->fetch()) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "services/index.php");
            exit;
        }

        $serviceType = trim(filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING));
        $serviceDate = $_POST['service_date'];
        $mileage = (int) $_POST['mileage'];
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $partsReplaced = trim(filter_input(INPUT_POST, 'parts_replaced', FILTER_SANITIZE_STRING));
        $serviceProvider = trim(filter_input(INPUT_POST, 'service_provider', FILTER_SANITIZE_STRING));
        $cost = (float) $_POST['cost'];
        $nextServiceDate = !empty($_POST['next_service_date']) ? $_POST['next_service_date'] : null;
        $nextServiceMileage = !empty($_POST['next_service_mileage']) ? (int) $_POST['next_service_mileage'] : null;
        $status = trim(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING));
        $remarks = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING));

        // Basic validation
        if (empty($serviceType) || empty($serviceDate) || empty($status)) {
            $_SESSION['flash_error'] = "Please fill in all required fields.";
            header("Location: " . BASE_URL . "services/create.php");
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO service_records (
                vehicle_id, service_type, service_date, mileage, description, 
                parts_replaced, service_provider, cost, next_service_date, 
                next_service_mileage, status, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $vehicleId, $serviceType, $serviceDate, $mileage, $description, 
            $partsReplaced, $serviceProvider, $cost, $nextServiceDate, 
            $nextServiceMileage, $status, $remarks
        ]);
        
        $serviceId = $pdo->lastInsertId();
        
        // Update vehicle's current mileage if this service has higher mileage
        $stmtUpdateMileage = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ? AND current_mileage < ?");
        $stmtUpdateMileage->execute([$mileage, $vehicleId, $mileage]);
        
        // Setup automatic reminders if next dates are provided
        if ($nextServiceDate || $nextServiceMileage) {
            // For now just basic tracking, more advanced reminder logic in phase 9/10
        }
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Added service record', 'service_records', $serviceId]);
        
        $pdo->commit();
        
        $_SESSION['flash_success'] = "Service record added successfully!";
        header("Location: " . BASE_URL . "services/index.php");
        exit;
    } elseif ($action === 'update') {
        $serviceId = (int) $_POST['service_id'];
        $vehicleId = (int) $_POST['vehicle_id'];
        
        // Verify user owns this vehicle and service
        $vCheckStmt = $pdo->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
        $vCheckStmt->execute([$vehicleId, $userId]);
        if (!$vCheckStmt->fetch()) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "services/index.php");
            exit;
        }

        $serviceType = trim(filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING));
        $serviceDate = $_POST['service_date'];
        $mileage = (int) $_POST['mileage'];
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $partsReplaced = trim(filter_input(INPUT_POST, 'parts_replaced', FILTER_SANITIZE_STRING));
        $serviceProvider = trim(filter_input(INPUT_POST, 'service_provider', FILTER_SANITIZE_STRING));
        $cost = (float) $_POST['cost'];
        $nextServiceDate = !empty($_POST['next_service_date']) ? $_POST['next_service_date'] : null;
        $nextServiceMileage = !empty($_POST['next_service_mileage']) ? (int) $_POST['next_service_mileage'] : null;
        $status = trim(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING));
        $remarks = trim(filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING));

        // Basic validation
        if (empty($serviceType) || empty($serviceDate) || empty($status) || empty($serviceId)) {
            $_SESSION['flash_error'] = "Please fill in all required fields.";
            header("Location: " . BASE_URL . "services/edit.php?id=" . $serviceId);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE service_records SET 
                vehicle_id = ?, service_type = ?, service_date = ?, mileage = ?, description = ?, 
                parts_replaced = ?, service_provider = ?, cost = ?, next_service_date = ?, 
                next_service_mileage = ?, status = ?, remarks = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $vehicleId, $serviceType, $serviceDate, $mileage, $description, 
            $partsReplaced, $serviceProvider, $cost, $nextServiceDate, 
            $nextServiceMileage, $status, $remarks,
            $serviceId
        ]);
        
        // Update vehicle's current mileage if this service has higher mileage
        $stmtUpdateMileage = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ? AND current_mileage < ?");
        $stmtUpdateMileage->execute([$mileage, $vehicleId, $mileage]);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Updated service record', 'service_records', $serviceId]);
        
        $_SESSION['flash_success'] = "Service record updated successfully!";
        header("Location: " . BASE_URL . "services/index.php");
        exit;

    } elseif ($action === 'delete') {
        $serviceId = (int) $_POST['service_id'];

        // Verify ownership via join
        $checkStmt = $pdo->prepare("
            SELECT sr.id FROM service_records sr
            JOIN vehicles v ON sr.vehicle_id = v.id
            WHERE sr.id = ? AND v.user_id = ?
        ");
        $checkStmt->execute([$serviceId, $userId]);
        if (!$checkStmt->fetch()) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "services/index.php");
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM service_records WHERE id = ?");
        $stmt->execute([$serviceId]);
        
        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Deleted service record', 'service_records', $serviceId]);
        
        $_SESSION['flash_success'] = "Service record deleted successfully!";
        header("Location: " . BASE_URL . "services/index.php");
        exit;
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Service Action Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred. Please try again.";
    
    if ($action === 'create') {
        header("Location: " . BASE_URL . "services/create.php");
    } else {
        header("Location: " . BASE_URL . "services/index.php");
    }
    exit;
}
