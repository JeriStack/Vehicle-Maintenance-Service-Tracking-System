<?php
// auth/change_password.php
$pageTitle = 'Change Password';
require_once __DIR__ . '/../includes/header.php';

// Ensure user is logged in
requireLogin();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo BASE_URL; ?>actions/profile_action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="update_password">
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">New Password</label>
                        <input type="password" class="form-control" name="new_password" required minlength="8">
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>settings/index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-lock me-1"></i> Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
