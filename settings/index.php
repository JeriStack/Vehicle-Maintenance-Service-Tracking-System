<?php
// settings/index.php
$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user_id'];

try {
    $pdo = Database::getInstance();
    
    // 1. Get User Profile
    $stmtU = $pdo->prepare("SELECT username, email, created_at FROM users WHERE id = ?");
    $stmtU->execute([$userId]);
    $user = $stmtU->fetch();
    
    // 2. Get Vehicle Preferences
    $stmtP = $pdo->prepare("SELECT * FROM vehicle_preferences WHERE user_id = ?");
    $stmtP->execute([$userId]);
    $prefs = $stmtP->fetch();
    
    if (!$prefs) {
        // Create default if missing
        $pdo->prepare("INSERT INTO vehicle_preferences (user_id) VALUES (?)")->execute([$userId]);
        $stmtP->execute([$userId]);
        $prefs = $stmtP->fetch();
    }
    
    // 3. Get Notification Settings
    $stmtN = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
    $stmtN->execute([$userId]);
    $notifs = $stmtN->fetch();
    
    if (!$notifs) {
        // Create default if missing
        $pdo->prepare("INSERT INTO notification_settings (user_id) VALUES (?)")->execute([$userId]);
        $stmtN->execute([$userId]);
        $notifs = $stmtN->fetch();
    }
    
} catch (PDOException $e) {
    error_log("Settings Load Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "Failed to load settings.";
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="bi bi-gear text-secondary me-2"></i>Settings</h1>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="nav flex-column nav-pills p-3 gap-2" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active text-start py-3" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab"><i class="bi bi-person me-2"></i> Account Profile</button>
                    <button class="nav-link text-start py-3" id="v-pills-preferences-tab" data-bs-toggle="pill" data-bs-target="#v-pills-preferences" type="button" role="tab"><i class="bi bi-sliders me-2"></i> Service Preferences</button>
                    <button class="nav-link text-start py-3" id="v-pills-notifications-tab" data-bs-toggle="pill" data-bs-target="#v-pills-notifications" type="button" role="tab"><i class="bi bi-bell me-2"></i> Notifications</button>
                    <a href="<?php echo BASE_URL; ?>auth/change_password.php" class="nav-link text-start py-3 text-dark"><i class="bi bi-shield-lock me-2"></i> Security</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9 mb-4">
        <div class="tab-content" id="v-pills-tabContent">
            
            <!-- Account Profile -->
            <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">Account Profile</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?php echo BASE_URL; ?>actions/settings_action.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium text-muted">Account Created</label>
                                <div><?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Profile</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Service Preferences -->
            <div class="tab-pane fade" id="v-pills-preferences" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">Vehicle & Service Preferences</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?php echo BASE_URL; ?>actions/settings_action.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="update_preferences">
                            
                            <div class="alert alert-info py-2 small mb-4">
                                These values act as defaults when creating new service records.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Default Service Interval (Months)</label>
                                    <input type="number" class="form-control" name="default_service_interval" value="<?php echo htmlspecialchars($prefs['default_service_interval']); ?>" min="1" max="60" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Default Mileage Interval (km)</label>
                                    <input type="number" class="form-control" name="default_mileage_interval" value="<?php echo htmlspecialchars($prefs['default_mileage_interval']); ?>" min="1000" step="500" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Preferred Service Provider</label>
                                <input type="text" class="form-control" name="preferred_service_provider" value="<?php echo htmlspecialchars($prefs['preferred_service_provider']); ?>" placeholder="E.g. Bob's Auto Shop">
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Notifications -->
            <div class="tab-pane fade" id="v-pills-notifications" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold text-dark">Notification Settings</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="<?php echo BASE_URL; ?>actions/settings_action.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="update_notifications">
                            
                            <h6 class="fw-bold mb-3">Reminder Timing</h6>
                            <div class="mb-4 w-50">
                                <label class="form-label fw-medium">Notify me</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="reminder_days" value="<?php echo htmlspecialchars($notifs['reminder_days']); ?>" min="1" max="90" required>
                                    <span class="input-group-text">days before due</span>
                                </div>
                            </div>
                            
                            <h6 class="fw-bold mb-3 border-top pt-4">In-App Alerts</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="maintenance_reminder" name="maintenance_reminder" value="1" <?php echo $notifs['maintenance_reminder'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_reminder">Upcoming Maintenance Reminders</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="registration_reminder" name="registration_reminder" value="1" <?php echo $notifs['registration_reminder'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="registration_reminder">Registration Expiry Reminders</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="service_due_reminder" name="service_due_reminder" value="1" <?php echo $notifs['service_due_reminder'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="service_due_reminder">Service Due Today Alerts</label>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="overdue_reminder" name="overdue_reminder" value="1" <?php echo $notifs['overdue_reminder'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="overdue_reminder">Overdue Service Alerts</label>
                            </div>
                            
                            <h6 class="fw-bold mb-3 border-top pt-4">External Delivery (Mock)</h6>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" value="1" <?php echo $notifs['email_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">Send Email Notifications</label>
                            </div>
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="push_notifications" name="push_notifications" value="1" <?php echo $notifs['push_notifications'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="push_notifications">Enable Push Notifications</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Notifications</button>
                        </form>
                    </div>
                </div>
            </div>
            

            
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
