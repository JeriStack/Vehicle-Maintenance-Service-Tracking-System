<?php
// config/constants.php

// Define Base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Ensure base_dir doesn't include specific PHP scripts like index.php
$base_dir = preg_replace('/\/[^\/]+\.php$/', '', $base_dir);

define('BASE_URL', $protocol . '://' . $host . $base_dir . '/');

// Application Constants
define('APP_NAME', 'Vehicle Maintenance Service Tracking System');
define('APP_VERSION', '1.0.0');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vehicle_maintenance_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
