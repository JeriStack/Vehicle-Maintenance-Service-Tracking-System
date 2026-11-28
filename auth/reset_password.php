<?php
// auth/reset_password.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

requireGuest();

$token = (string) ($_GET['token'] ?? '');
$email = \VMS\Security\EmailNormalizer::normalize((string) ($_GET['email'] ?? ''));

if (empty($token) || empty($email)) {
    $_SESSION['flash_error'] = "Invalid password reset request.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

try {
    $pdo = Database::getInstance();
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1");
    $stmt->execute([$email, $tokenHash]);
    $resetRecord = $stmt->fetch();

    if (!$resetRecord) {
        $_SESSION['flash_error'] = "This password reset link is invalid or has expired. Please request a new one.";
        header("Location: " . BASE_URL . "auth/forgot_password.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Reset Password Check Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/app.log');
    $_SESSION['flash_error'] = "A database error occurred. Please try again.";
    header("Location: " . BASE_URL . "auth/forgot_password.php");
    exit;
}

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
    <title>Set New Password - <?php echo APP_NAME; ?></title>
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
            <p class="auth-subtitle">Create a new secure password</p>
        </div>
        
        <?php require_once __DIR__ . '/../includes/flash.php'; displayFlashMessages(); ?>
        
        <form action="<?php echo BASE_URL; ?>actions/reset_password_action.php" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control has-password-toggle" id="password" name="password" minlength="8" placeholder="At least 8 characters" required autofocus>
                    <button type="button" class="password-toggle-btn" data-password-toggle data-target="#password" aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input type="password" class="form-control has-password-toggle" id="confirm_password" name="confirm_password" minlength="8" placeholder="Re-enter new password" required>
                    <button type="button" class="password-toggle-btn" data-password-toggle data-target="#confirm_password" aria-label="Show password" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-grid mb-4">
                <button type="submit" class="auth-btn text-white">Save New Password</button>
            </div>
        </form>
        
        <div class="text-center">
            <p class="text-muted mb-0"><a href="login.php" class="text-decoration-none fw-semibold">Cancel and Back to Login</a></p>
        </div>
    </div>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/password-toggle.js"></script>
</body>
</html>

