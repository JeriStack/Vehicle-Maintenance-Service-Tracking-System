<?php
// auth/forgot_password.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect to dashboard if already logged in
requireGuest();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-card login-card">
        <div class="auth-header">
            <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="VMS Tracker Logo" class="img-fluid auth-logo">
            <h4 class="auth-title">VMS Tracker</h4>
            <p class="auth-subtitle">Reset your account password</p>
        </div>
        
        <?php require_once __DIR__ . '/../includes/flash.php'; displayFlashMessages(); ?>
        
        <form action="<?php echo BASE_URL; ?>actions/forgot_password_action.php" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <p class="small text-white-50 mb-3">
                Enter your registered email address below. We'll send you a secure link to reset your password.
            </p>

            <div class="mb-4">
                <label for="email" class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required autofocus>
                </div>
            </div>
            
            <div class="d-grid mb-4">
                <button type="submit" class="auth-btn text-white">Send Reset Link</button>
            </div>
        </form>
        
        <div class="text-center">
            <p class="text-muted mb-0">Remember your password? <a href="login.php" class="text-decoration-none fw-semibold">Back to Login</a></p>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

