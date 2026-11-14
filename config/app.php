<?php
// config/app.php

$basePath = dirname(__DIR__);

// 1. PSR-4 Autoloading (Composer or Fallback)
$autoloader = $basePath . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    spl_autoload_register(function (string $class) use ($basePath) {
        $prefix = 'VMS\\';
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        $seederPrefix = 'Database\\Seeders\\';
        if (str_starts_with($class, $seederPrefix)) {
            $relativeClass = substr($class, strlen($seederPrefix));
            $file = $basePath . '/database/seeders/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

// 2. Load Global Helpers
$helpersFile = $basePath . '/app/Support/helpers.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
}

// 3. Bootstrap Configuration
if (class_exists(\VMS\Core\Config::class)) {
    \VMS\Core\Config::boot($basePath);
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/websocket.php';
require_once __DIR__ . '/mail.php';

// Set default timezone
date_default_timezone_set('UTC'); // Or configure accordingly
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
// 4. Start hardened session with security cookie flags
if (class_exists(\VMS\Core\Session::class)) {
    \VMS\Core\Session::start();
} elseif (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
