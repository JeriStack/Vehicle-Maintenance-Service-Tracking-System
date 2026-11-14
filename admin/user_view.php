<?php
// admin/user_view.php
$pageTitle = 'View User Details';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$userId) {
    $_SESSION['flash_error'] = "Invalid user ID.";
    header("Location: users.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    
    // Get user details
    $stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as vehicle_count,
               (SELECT COUNT(*) FROM service_records sr JOIN vehicles v ON sr.vehicle_id = v.id WHERE v.user_id = u.id) as service_count
        FROM users u WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['flash_error'] = "User not found.";
        header("Location: users.php");
        exit;
    }
    
    // Get user vehicles
    $vStmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC");
    $vStmt->execute([$userId]);
    $vehicles = $vStmt->fetchAll();
    
    // Get user recent services
    $sStmt = $pdo->prepare("
        SELECT sr.*, v.make, v.model, v.plate_number 
        FROM service_records sr
        JOIN vehicles v ON sr.vehicle_id = v.id
        WHERE v.user_id = ?
        ORDER BY sr.service_date DESC
        LIMIT 10
    ");
    $sStmt->execute([$userId]);
    $services = $sStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Admin User View Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "An error occurred while loading user data.";
    header("Location: users.php");
    exit;
}
?>

<div class="row">
    <!-- User Profile Card -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center p-4">
                <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="mb-3">
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge bg-primary rounded-pill px-3 py-2">Administrator</span>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-pill px-3 py-2">Vehicle Owner</span>
                    <?php endif; ?>
                </div>
                
                <ul class="list-group list-group-flush text-start mt-4">
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Phone</span>
                        <span class="fw-medium"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Registered</span>
                        <span class="fw-medium"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Vehicles</span>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo $user['vehicle_count']; ?></span>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0">
                        <span class="text-muted">Services</span>
                        <span class="badge bg-light text-dark rounded-pill"><?php echo $user['service_count']; ?></span>
                    </li>
                </ul>
            </div>
            <div class="card-footer bg-white border-top-0 p-3 text-center d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#changeRoleModal">
                    Manage Role
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#toggleStatusModal">
                    <?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate Account' : 'Activate Account'; ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- User Data Tabs -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom pt-3 pb-0">
                <ul class="nav nav-tabs border-bottom-0" id="userTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-dark fw-medium" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button" role="tab" aria-controls="vehicles" aria-selected="true">Vehicles</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-dark fw-medium" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab" aria-controls="services" aria-selected="false">Recent Services</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="userTabsContent">
                    <!-- Vehicles Tab -->
                    <div class="tab-pane fade show active" id="vehicles" role="tabpanel" aria-labelledby="vehicles-tab">
                        <?php if (empty($vehicles)): ?>
                            <div class="text-center text-muted p-4">
                                <i class="bi bi-car-front fs-2 d-block mb-2"></i>
                                No vehicles found for this user.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Vehicle</th>
                                            <th>Plate No.</th>
                                            <th>Status</th>
                                            <th>Added On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vehicles as $v): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($v['year'] . ' ' . $v['color']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($v['plate_number']); ?></td>
                                                <td>
                                                    <?php if ($v['status'] === 'Active'): ?>
                                                        <span class="badge bg-success rounded-pill px-2">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary rounded-pill px-2"><?php echo htmlspecialchars($v['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($v['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Services Tab -->
                    <div class="tab-pane fade" id="services" role="tabpanel" aria-labelledby="services-tab">
                        <?php if (empty($services)): ?>
                            <div class="text-center text-muted p-4">
                                <i class="bi bi-tools fs-2 d-block mb-2"></i>
                                No service records found for this user.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Vehicle</th>
                                            <th>Service Type</th>
                                            <th>Cost</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $s): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($s['service_date'])); ?></td>
                                                <td>
                                                    <div class="fw-medium text-dark"><?php echo htmlspecialchars($s['make'] . ' ' . $s['model']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($s['plate_number']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($s['service_type']); ?></td>
                                                <td>$<?php echo number_format($s['cost'], 2); ?></td>
                                                <td>
                                                    <?php if ($s['status'] === 'Completed'): ?>
                                                        <span class="badge bg-success rounded-pill px-2">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark rounded-pill px-2"><?php echo htmlspecialchars($s['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Role Modal -->
<div class="modal fade" id="changeRoleModal" tabindex="-1" aria-labelledby="changeRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="changeRoleModalLabel">Manage User Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>actions/admin_user_action.php" method="POST">
                <div class="modal-body p-4">
                    <?php echo \VMS\Core\Csrf::field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="action" value="change_role">
                    
                    <?php if ($user['id'] === currentUserId()): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i> You cannot change your own role.
                        </div>
                    <?php else: ?>
                        <p class="mb-3">Select a new role for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>:</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="new_role" id="roleUser" value="user" <?php echo $user['role'] === 'user' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="roleUser">
                                Vehicle Owner (Normal User)
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="new_role" id="roleAdmin" value="admin" <?php echo $user['role'] === 'admin' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="roleAdmin">
                                Administrator (Full Access)
                            </label>
                        </div>
                        <div class="mb-0">
                            <label for="roleAdminPassword" class="form-label fw-medium">Confirm Your Administrator Password</label>
                            <input type="password" class="form-control" id="roleAdminPassword" name="admin_password" required autocomplete="current-password">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($user['id'] !== currentUserId()): ?>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold" id="toggleStatusModalLabel">
                    <?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate Account' : 'Activate Account'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo BASE_URL; ?>actions/admin_user_action.php" method="POST">
                <div class="modal-body p-4">
                    <?php echo \VMS\Core\Csrf::field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="action" value="toggle_status">

                    <?php if ($user['id'] === currentUserId()): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i> You cannot deactivate your own account.
                        </div>
                    <?php else: ?>
                        <p class="mb-3">
                            Are you sure you want to
                            <strong><?php echo ($user['status'] ?? 'active') === 'active' ? 'deactivate' : 'activate'; ?></strong>
                            the account for <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>?
                        </p>
                        <div class="mb-0">
                            <label for="statusAdminPassword" class="form-label fw-medium">Confirm Your Administrator Password</label>
                            <input type="password" class="form-control" id="statusAdminPassword" name="admin_password" required autocomplete="current-password">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if ($user['id'] !== currentUserId()): ?>
                        <button type="submit" class="btn btn-warning">Confirm</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
