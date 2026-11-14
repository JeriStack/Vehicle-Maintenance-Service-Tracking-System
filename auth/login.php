<?php
// auth/login.php
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
    <title>Login - <?php echo APP_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-card login-card">
        <div class="auth-header">
            <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="VMS Tracker Logo" class="img-fluid auth-logo">
            <h4 class="auth-title">VMS Tracker</h4>
            <p class="auth-subtitle">Sign in to your account</p>
        </div>
        
        <?php require_once __DIR__ . '/../includes/flash.php'; displayFlashMessages(); ?>
        
        <form action="<?php echo BASE_URL; ?>actions/login_action.php" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control has-password-toggle" id="password" name="password" required>
                    <button type="button" class="password-toggle-btn" data-password-toggle data-target="#password" aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me" value="1">
                    <label class="form-check-label small user-select-none" for="remember_me">
                        Remember me
                    </label>
                </div>
                <a href="<?php echo BASE_URL; ?>auth/forgot_password.php" class="text-decoration-none small">Forgot password?</a>
            </div>
            
            <div class="d-grid mb-4">
                <button type="submit" class="auth-btn text-white">Login</button>
            </div>
        </form>
        
        <div class="text-center">
            <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold">Register here</a></p>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/password-toggle.js"></script>
</body>
</html>
