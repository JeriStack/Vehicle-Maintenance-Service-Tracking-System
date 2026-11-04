<?php
// vehicles/index.php
$pageTitle = 'Vehicles';
require_once __DIR__ . '/../includes/header.php';

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $vehicles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Vehicles Index Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $vehicles = [];
    echo '<div class="alert alert-danger">Failed to load vehicles.</div>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">My Vehicles</h1>
    <a href="<?php echo BASE_URL; ?>vehicles/create.php" class="btn btn-primary shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Vehicle
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($vehicles)): ?>
            <div class="text-center p-5">
                <div class="display-1 text-muted mb-3 opacity-25"><i class="bi bi-car-front"></i></div>
                <h4 class="text-secondary">No vehicles found</h4>
                <p class="text-muted">You haven't added any vehicles yet.</p>
                <a href="<?php echo BASE_URL; ?>vehicles/create.php" class="btn btn-primary mt-2">Add Your First Vehicle</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Vehicle</th>
                            <th>Plate Number</th>
                            <th>Type</th>
                            <th>Registration Expiry</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($vehicle['year'] . ' • ' . $vehicle['color']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1"><?php echo htmlspecialchars($vehicle['plate_number']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                <td>
                                    <?php 
                                    $expiry = strtotime($vehicle['registration_expiry']);
                                    $now = time();
                                    $diffDays = ($expiry - $now) / (60 * 60 * 24);
                                    
                                    if ($diffDays < 0) {
                                        echo '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>' . date('M d, Y', $expiry) . '</span>';
                                    } elseif ($diffDays < 30) {
                                        echo '<span class="text-warning text-dark fw-bold"><i class="bi bi-exclamation-circle me-1"></i>' . date('M d, Y', $expiry) . '</span>';
                                    } else {
                                        echo '<span class="text-success">' . date('M d, Y', $expiry) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($vehicle['status'] === 'Active'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 border border-success border-opacity-25 rounded-pill">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 border border-secondary border-opacity-25 rounded-pill"><?php echo htmlspecialchars($vehicle['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo BASE_URL; ?>vehicles/view.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="<?php echo BASE_URL; ?>vehicles/edit.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <a href="<?php echo BASE_URL; ?>vehicles/delete.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                                    </div>
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
