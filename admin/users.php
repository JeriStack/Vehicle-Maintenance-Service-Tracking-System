<?php
// admin/users.php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

try {
    $pdo = Database::getInstance();
    
    // Handle Search and Filter
    $search = $_GET['search'] ?? '';
    $roleFilter = $_GET['role'] ?? '';
    
    $query = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as vehicle_count,
               (SELECT COUNT(*) FROM service_records sr JOIN vehicles v ON sr.vehicle_id = v.id WHERE v.user_id = u.id) as service_count
        FROM users u
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($roleFilter)) {
        $query .= " AND u.role = ?";
        $params[] = $roleFilter;
    }
    
    $query .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin Users Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load users.";
    $users = [];
}
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h6 class="m-0 fw-bold text-dark">User Management</h6>
            </div>
            <div class="col-md-8">
                <form action="" method="GET" class="d-flex justify-content-md-end">
                    <select name="role" class="form-select form-select-sm me-2" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Vehicle Owner / User</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                    </select>
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <?php if (!empty($search) || !empty($roleFilter)): ?>
                        <a href="users.php" class="btn btn-sm btn-light ms-2" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Vehicles</th>
                        <th>Services</th>
                        <th>Created</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-primary rounded-pill px-3">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3">User</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $u['vehicle_count']; ?></td>
                                <td><?php echo $u['service_count']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td class="pe-4 text-end">
                                    <a href="user_view.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
