<?php
// index.php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "dashboard/index.php");
} else {
    header("Location: " . BASE_URL . "auth/login.php");
}
exit;
