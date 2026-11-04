<?php
// admin/settings.php
$pageTitle = 'Admin Settings';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-person-badge me-2"></i>Personal Settings</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Manage your personal administrator profile, update your contact details, and change your password.</p>
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>settings/profile.php" class="btn btn-outline-primary text-start">
                        <i class="bi bi-person me-2"></i> Update Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>settings/password.php" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-key me-2"></i> Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-sliders me-2"></i>System Configuration</h6>
            </div>
            <div class="card-body text-center p-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-gear fs-1 opacity-50"></i>
                </div>
                <h5 class="fw-bold text-dark">System configuration is currently handled via environment files.</h5>
                <p class="text-muted mx-auto" style="max-width: 400px;">
                    Core system settings like database connections, application URL, and environment constants are managed in <code>config/constants.php</code> and <code>config/database.php</code>.
                </p>
                <p class="text-muted small mt-4">Future updates may introduce dynamic UI-based system configurations here.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
