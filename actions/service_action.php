<?php
// actions/service_action.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../helpers/websocket_helper.php';

use VMS\Core\Csrf;

// Protect action route
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

// CSRF Protection (constant-time)
if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Invalid request or session expired. Please try again.";
    header("Location: " . BASE_URL . "services/index.php");
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();

    if ($action === 'create') {
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

        // Verify user owns this vehicle
        $vCheckStmt = $pdo->prepare("SELECT id, current_mileage FROM vehicles WHERE id = ? AND user_id = ?");
        $vCheckStmt->execute([$vehicleId, $userId]);
        $vehicle = $vCheckStmt->fetch();
        if (!$vehicle) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "services/index.php");
            exit;
        }

        $serviceType = trim((string) ($_POST['service_type'] ?? ''));
        $serviceDate = trim((string) ($_POST['service_date'] ?? ''));
        $mileage = max(0, (int) ($_POST['mileage'] ?? 0));
        $description = trim((string) ($_POST['description'] ?? ''));
        $partsReplaced = trim((string) ($_POST['parts_replaced'] ?? ''));
        $serviceProvider = trim((string) ($_POST['service_provider'] ?? ''));
        $cost = max(0.0, (float) ($_POST['cost'] ?? 0));
        $nextServiceDate = !empty($_POST['next_service_date']) ? trim((string) $_POST['next_service_date']) : null;
        $nextServiceMileage = !empty($_POST['next_service_mileage']) ? max(0, (int) $_POST['next_service_mileage']) : null;
        $status = trim((string) ($_POST['status'] ?? 'Completed'));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        if ($serviceType === '' || $serviceDate === '' || $status === '') {
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

        $serviceId = (int) $pdo->lastInsertId();

        // Advance vehicle mileage & record mileage history log when mileage increases
        if ($mileage > (int) $vehicle['current_mileage']) {
            $stmtUpdateMileage = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ? AND user_id = ? AND current_mileage < ?");
            $stmtUpdateMileage->execute([$mileage, $vehicleId, $userId, $mileage]);

            $mlStmt = $pdo->prepare("INSERT INTO vehicle_mileage_logs (vehicle_id, mileage, source, recorded_by_user_id) VALUES (?, ?, 'SERVICE_RECORD', ?)");
            $mlStmt->execute([$vehicleId, $mileage, $userId]);
        }

        // Log action
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Added service record', 'service_records', $serviceId]);

        $pdo->commit();

        WebSocketHelper::serviceUpdated($userId, [
            'id'           => $serviceId,
            'vehicle_id'   => $vehicleId,
            'service_type' => $serviceType,
            'action'       => 'create'
        ]);

        $_SESSION['flash_success'] = "Service record added successfully!";
        header("Location: " . BASE_URL . "services/index.php");
        exit;

    } elseif ($action === 'update') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $vehicleId = (int) ($_POST['vehicle_id'] ?? 0);

        // 1. Verify user owns the EXISTING service record (prevents cross-tenant IDOR)
        $srCheckStmt = $pdo->prepare("
            SELECT sr.id
            FROM service_records sr
            JOIN vehicles v ON sr.vehicle_id = v.id
            WHERE sr.id = ? AND v.user_id = ?
        ");
        $srCheckStmt->execute([$serviceId, $userId]);
        if (!$srCheckStmt->fetch()) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "services/index.php");
            exit;
        }

        // 2. Verify user also owns the target vehicle
        $vCheckStmt = $pdo->prepare("SELECT id, current_mileage FROM vehicles WHERE id = ? AND user_id = ?");
        $vCheckStmt->execute([$vehicleId, $userId]);
        $vehicle = $vCheckStmt->fetch();
        if (!$vehicle) {
            $_SESSION['flash_error'] = "Permission denied.";
            header("Location: " . BASE_URL . "services/index.php");
            exit;
        }

        $serviceType = trim((string) ($_POST['service_type'] ?? ''));
        $serviceDate = trim((string) ($_POST['service_date'] ?? ''));
        $mileage = max(0, (int) ($_POST['mileage'] ?? 0));
        $description = trim((string) ($_POST['description'] ?? ''));
        $partsReplaced = trim((string) ($_POST['parts_replaced'] ?? ''));
        $serviceProvider = trim((string) ($_POST['service_provider'] ?? ''));
        $cost = max(0.0, (float) ($_POST['cost'] ?? 0));
        $nextServiceDate = !empty($_POST['next_service_date']) ? trim((string) $_POST['next_service_date']) : null;
        $nextServiceMileage = !empty($_POST['next_service_mileage']) ? max(0, (int) $_POST['next_service_mileage']) : null;
        $status = trim((string) ($_POST['status'] ?? 'Completed'));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        if ($serviceType === '' || $serviceDate === '' || $status === '' || $serviceId <= 0) {
            $_SESSION['flash_error'] = "Please fill in all required fields.";
            header("Location: " . BASE_URL . "services/edit.php?id=" . $serviceId);
            exit;
        }

        $pdo->beginTransaction();

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

        if ($mileage > (int) $vehicle['current_mileage']) {
            $stmtUpdateMileage = $pdo->prepare("UPDATE vehicles SET current_mileage = ? WHERE id = ? AND user_id = ? AND current_mileage < ?");
            $stmtUpdateMileage->execute([$mileage, $vehicleId, $userId, $mileage]);

            $mlStmt = $pdo->prepare("INSERT INTO vehicle_mileage_logs (vehicle_id, mileage, source, recorded_by_user_id) VALUES (?, ?, 'SERVICE_RECORD', ?)");
            $mlStmt->execute([$vehicleId, $mileage, $userId]);
        }

        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Updated service record', 'service_records', $serviceId]);

        $pdo->commit();

        WebSocketHelper::serviceUpdated($userId, [
            'id'           => $serviceId,
            'vehicle_id'   => $vehicleId,
            'service_type' => $serviceType,
            'action'       => 'update'
        ]);

        $_SESSION['flash_success'] = "Service record updated successfully!";
        header("Location: " . BASE_URL . "services/index.php");
        exit;

    } elseif ($action === 'delete') {
        $serviceId = (int) ($_POST['service_id'] ?? 0);

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

        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, 'Deleted service record', 'service_records', $serviceId]);

        WebSocketHelper::serviceUpdated($userId, [
            'id'     => $serviceId,
            'action' => 'delete'
        ]);

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
