<?php
// config/constants.php

// Define Base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Get the document root and the application root directory
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$app_dir = str_replace('\\', '/', dirname(__DIR__));

// The base directory is the application directory minus the document root
$base_dir = str_replace($doc_root, '', $app_dir);

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
