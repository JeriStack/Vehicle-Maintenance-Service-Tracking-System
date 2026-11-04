<?php
// tests/run_tests.php
/**
 * Vehicle Maintenance Service Tracking System - Native Unit Test Runner
 *
 * Runs all unit tests without requiring external composer packages or internet access.
 * Compatible with PHPUnit test cases.
 *
 * Usage:
 *   php tests/run_tests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$startTime = microtime(true);

require_once __DIR__ . '/TestCase.php';

// Discovered tests
$testDir = __DIR__ . '/Unit';
$testFiles = glob($testDir . '/*Test.php');

$totalTests = 0;
$totalAssertions = 0;
$passed = 0;
$failed = 0;
$failures = [];

echo PHP_EOL;
echo "====================================================================" . PHP_EOL;
echo " Vehicle Maintenance Service Tracking System - Unit Test Suite" . PHP_EOL;
echo " PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")" . PHP_EOL;
echo " Test Directory: " . $testDir . PHP_EOL;
echo "====================================================================" . PHP_EOL . PHP_EOL;

foreach ($testFiles as $file) {
    require_once $file;

    $className = 'Tests\\Unit\\' . basename($file, '.php');
    if (!class_exists($className)) {
        continue;
    }

    $reflection = new ReflectionClass($className);
    if ($reflection->isAbstract()) {
        continue;
    }

    $methods = array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), function($method) {
        return str_starts_with($method->getName(), 'test');
    });

    echo "Running " . basename($file) . " (" . count($methods) . " tests):" . PHP_EOL;

    foreach ($methods as $method) {
        $totalTests++;
        $methodName = $method->getName();
        $instance = new $className();

        try {
            $instance->runSetUp();
            $method->invoke($instance);

            if ($instance->getExpectedException() !== null) {
                throw new \Tests\AssertionException("Failed asserting that exception " . $instance->getExpectedException() . " was thrown.");
            }

            $instance->runTearDown();

            $totalAssertions += $instance->getAssertionCount();
            $passed++;
            echo "  [PASS] {$methodName}" . PHP_EOL;
        } catch (\Throwable $e) {
            $failed++;
            $failures[] = [
                'suite'     => $className,
                'test'      => $methodName,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine()
            ];
            echo "  [FAIL] {$methodName} -> " . $e->getMessage() . PHP_EOL;
        }
    }

    echo PHP_EOL;
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);
$mem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

echo "====================================================================" . PHP_EOL;
echo " Test Results Summary:" . PHP_EOL;
echo " Total Tests:      {$totalTests}" . PHP_EOL;
echo " Total Assertions: {$totalAssertions}" . PHP_EOL;
echo " Passed:           {$passed}" . PHP_EOL;
echo " Failed:           {$failed}" . PHP_EOL;
echo " Time Elapsed:     {$elapsed} ms" . PHP_EOL;
echo " Peak Memory:      {$mem} MB" . PHP_EOL;
echo "====================================================================" . PHP_EOL;

if ($failed > 0) {
    echo PHP_EOL . "FAILURES LIST:" . PHP_EOL;
    foreach ($failures as $idx => $f) {
        $num = $idx + 1;
        echo "{$num}) {$f['suite']}::{$f['test']}" . PHP_EOL;
        echo "   {$f['message']}" . PHP_EOL;
        echo "   at {$f['file']}:{$f['line']}" . PHP_EOL . PHP_EOL;
    }
    exit(1);
} else {
    echo PHP_EOL . "SUCCESS! All unit tests passed without any errors." . PHP_EOL . PHP_EOL;
    exit(0);
}

