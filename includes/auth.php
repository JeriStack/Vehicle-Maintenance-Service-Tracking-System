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
        if (isAdmin()) {
            header("Location: " . BASE_URL . "admin/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "dashboard/index.php");
        }
        exit;
    }
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isUser() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("HTTP/1.1 403 Forbidden");
        $_SESSION['flash_error'] = "Access denied. Administrator privileges required.";
        header("Location: " . BASE_URL . "dashboard/index.php");
        exit;
    }
}

function requireUser() {
    requireLogin();
    if (!isUser()) {
        header("HTTP/1.1 403 Forbidden");
        $_SESSION['flash_error'] = "Access denied.";
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUserRole() {
    return $_SESSION['role'] ?? null;
}
