<?php
declare(strict_types=1);

// public/index.php
/**
 * Vehicle Maintenance Service Tracking System (VMS)
 * Front Controller and Public Entry Point
 */

define('VMS_START', microtime(true));

$basePath = dirname(__DIR__);

// 1. Load Composer autoloader
$autoloader = $basePath . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    // Fallback PSR-4 autoloader for clean checkouts prior to composer install
    spl_autoload_register(function (string $class) use ($basePath) {
        $prefix = 'VMS\\';
        if (str_starts_with($class, $prefix)) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $basePath . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

// 2. Instantiate and run application
$app = new \VMS\Core\App($basePath);
$app->run();
