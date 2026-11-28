<?php
declare(strict_types=1);

// tests/bootstrap.php
/**
 * PHPUnit and Standalone Test Runner Bootstrap file
 */

if (!defined('VMS_UNIT_TESTING')) {
    define('VMS_UNIT_TESTING', true);
}

require_once __DIR__ . '/TestCase.php';

// 1. Load Composer autoloader or fallback PSR-4 autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    spl_autoload_register(function (string $class) {
        $prefix = 'VMS\\';
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

// 2. Load helper functions if available
$helpersFile = dirname(__DIR__) . '/app/Support/helpers.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
}

// 3. Load baseline constants and websocket configuration
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/websocket.php';

// 4. Initialize session environment for CLI test suite if not already active
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}
