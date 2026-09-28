<?php
// includes/auth.php
require_once __DIR__ . '/../config/app.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        header("Location: " . BASE_URL . "dashboard/index.php");
        exit;
    }
}
