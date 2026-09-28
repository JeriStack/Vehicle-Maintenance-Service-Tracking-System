<?php
// config/app.php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

// Set default timezone
date_default_timezone_set('UTC'); // Or configure accordingly

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
