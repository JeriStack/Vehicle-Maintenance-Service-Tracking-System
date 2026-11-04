<?php
// tests/run_tests.php
/**
 * Vehicle Maintenance Service Tracking System - Native Test Runner
 *
 * Runs unit, smoke, and database integration tests without external dependencies.
 *
 * Usage:
 *   php tests/run_tests.php            # Runs Unit and Smoke test suites
 *   php tests/run_tests.php --unit     # Runs Unit tests only
 *   php tests/run_tests.php --smoke    # Runs Smoke tests only
 *   php tests/run_tests.php --database # Runs Database integration tests only
 *   php tests/run_tests.php --all      # Runs all test suites (Unit, Smoke, Database)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$startTime = microtime(true);

require_once __DIR__ . '/bootstrap.php';

// Discovered test suites
$isSmokeOnly    = in_array('--smoke', $argv, true);
$isUnitOnly     = in_array('--unit', $argv, true);
$isDatabaseOnly = in_array('--database', $argv, true);
$isAll          = in_array('--all', $argv, true);

$unitFiles     = glob(__DIR__ . '/Unit/*Test.php') ?: [];
$smokeFiles    = glob(__DIR__ . '/Smoke/*Test.php') ?: [];
$databaseFiles = glob(__DIR__ . '/Integration/Database/*Test.php') ?: [];

$testFiles = [];
if ($isSmokeOnly) {
    $testFiles = $smokeFiles;
    $suiteLabel = "Smoke Test Suite";
} elseif ($isUnitOnly) {
    $testFiles = $unitFiles;
    $suiteLabel = "Unit Test Suite";
} elseif ($isDatabaseOnly) {
    $testFiles = $databaseFiles;
    $suiteLabel = "Database Integration Test Suite";
} elseif ($isAll) {
    $testFiles = array_merge($unitFiles, $smokeFiles, $databaseFiles);
    $suiteLabel = "Complete Test Suite (Unit, Smoke & Database Integration)";
} else {
    // Default: Unit & Smoke
    $testFiles = array_merge($unitFiles, $smokeFiles);
    $suiteLabel = "Automated Test Suite (Unit & Smoke)";
}

$totalTests = 0;
$totalAssertions = 0;
$passed = 0;
$failed = 0;
$failures = [];

echo PHP_EOL;
echo "====================================================================" . PHP_EOL;
echo " Vehicle Maintenance Service Tracking System - {$suiteLabel}" . PHP_EOL;
echo " PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")" . PHP_EOL;
echo " Target Files: " . count($testFiles) . " test files discovered" . PHP_EOL;
echo "====================================================================" . PHP_EOL . PHP_EOL;

foreach ($testFiles as $file) {
    require_once $file;

    // Resolve class name from relative path
    $relPath = str_replace([__DIR__ . '/', __DIR__ . '\\', '.php'], ['', '', ''], $file);
    $className = 'Tests\\' . str_replace(['/', '\\'], '\\', $relPath);

    if (!class_exists($className)) {
        // Fallback checks
        $baseName = basename($file, '.php');
        $className = "Tests\\Unit\\{$baseName}";
        if (!class_exists($className)) {
            continue;
        }
    }

    // Call static setUpBeforeClass if implemented
    if (method_exists($className, 'setUpBeforeClass')) {
        $className::setUpBeforeClass();
    }

    $reflection = new ReflectionClass($className);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

    $testMethods = array_filter($methods, function ($method) {
        return str_starts_with($method->name, 'test');
    });

    if (empty($testMethods)) {
        continue;
    }

    echo "Running " . basename($file) . " (" . count($testMethods) . " tests):" . PHP_EOL;

    foreach ($testMethods as $method) {
        $testInstance = new $className();

        $totalTests++;

        // Execute setUp() if defined
        if (method_exists($testInstance, 'runSetUp')) {
            $testInstance->runSetUp();
        } elseif (is_callable([$testInstance, 'setUp'])) {
            $testInstance->setUp();
        }

        try {
            $testInstance->{$method->name}();
            $passed++;
            $totalAssertions += $testInstance->getAssertionCount();
            echo "  [PASS] " . $method->name . PHP_EOL;
        } catch (\Tests\AssertionException $e) {
            $failed++;
            $totalAssertions += $testInstance->getAssertionCount();
            $failures[] = [
                'test' => $className . '::' . $method->name,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            echo "  [FAIL] " . $method->name . " -> " . $e->getMessage() . PHP_EOL;
        } catch (\Throwable $e) {
            $failed++;
            $failures[] = [
                'test' => $className . '::' . $method->name,
                'message' => 'Uncaught exception: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            echo "  [ERROR] " . $method->name . " -> " . $e->getMessage() . PHP_EOL;
        }

        // Execute tearDown() if defined
        if (method_exists($testInstance, 'runTearDown')) {
            $testInstance->runTearDown();
        } elseif (is_callable([$testInstance, 'tearDown'])) {
            $testInstance->tearDown();
        }
    }

    // Call static tearDownAfterClass if implemented
    if (method_exists($className, 'tearDownAfterClass')) {
        $className::tearDownAfterClass();
    }

    echo PHP_EOL;
}

$elapsedTime = (microtime(true) - $startTime) * 1000;
$memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;

echo "====================================================================" . PHP_EOL;
echo " Test Results Summary:" . PHP_EOL;
echo sprintf(" Total Tests:      %d\n", $totalTests);
echo sprintf(" Total Assertions: %d\n", $totalAssertions);
echo sprintf(" Passed:           %d\n", $passed);
echo sprintf(" Failed:           %d\n", $failed);
echo sprintf(" Time Elapsed:     %.2f ms\n", $elapsedTime);
echo sprintf(" Peak Memory:      %d MB\n", $memoryPeak);
echo "====================================================================" . PHP_EOL . PHP_EOL;

if ($failed > 0) {
    echo "FAILURES LIST:" . PHP_EOL;
    foreach ($failures as $i => $fail) {
        echo ($i + 1) . ") " . $fail['test'] . PHP_EOL;
        echo "   " . $fail['message'] . PHP_EOL;
        echo "   at " . $fail['file'] . ":" . $fail['line'] . PHP_EOL . PHP_EOL;
    }
    exit(1);
} else {
    echo "SUCCESS! All unit tests passed without any errors." . PHP_EOL . PHP_EOL;
    exit(0);
}
