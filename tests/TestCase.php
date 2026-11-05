<?php
// tests/TestCase.php
namespace Tests;

class AssertionException extends \Exception {}
class SkippedTestException extends \Exception {}

if (class_exists('\PHPUnit\Framework\TestCase')) {
    abstract class BaseTestCase extends \PHPUnit\Framework\TestCase {}
} else {
    abstract class BaseTestCase {
        protected $assertionCount = 0;
        protected $expectedException = null;

        public function getAssertionCount() {
            return $this->assertionCount;
        }

        public function markTestSkipped(string $message = ''): void {
            throw new SkippedTestException($message);
        }

        public function expectException($exceptionClass) {
            $this->expectedException = $exceptionClass;
        }

        public function getExpectedException() {
            return $this->expectedException;
        }

        public function clearExpectedException() {
            $this->expectedException = null;
        }

        protected function setUp(): void {}
        protected function tearDown(): void {}

        public function runSetUp() {
            $this->setUp();
        }

        public function runTearDown() {
            $this->tearDown();
        }

        public function assertTrue($condition, $message = '') {
            $this->assertionCount++;
            if ($condition !== true) {
                $msg = $message ?: "Failed asserting that " . var_export($condition, true) . " is true.";
                throw new AssertionException($msg);
            }
        }

        public function assertFalse($condition, $message = '') {
            $this->assertionCount++;
            if ($condition !== false) {
                $msg = $message ?: "Failed asserting that " . var_export($condition, true) . " is false.";
                throw new AssertionException($msg);
            }
        }

        public function assertEquals($expected, $actual, $message = '') {
            $this->assertionCount++;
            if ($expected != $actual) {
                $msg = $message ?: "Failed asserting that " . var_export($actual, true) . " matches expected " . var_export($expected, true) . ".";
                throw new AssertionException($msg);
            }
        }

        public function assertSame($expected, $actual, $message = '') {
            $this->assertionCount++;
            if ($expected !== $actual) {
                $msg = $message ?: "Failed asserting that " . var_export($actual, true) . " is strictly identical to " . var_export($expected, true) . ".";
                throw new AssertionException($msg);
            }
        }

        public function assertNotSame($expected, $actual, $message = '') {
            $this->assertionCount++;
            if ($expected === $actual) {
                $msg = $message ?: "Failed asserting that values are not strictly identical.";
                throw new AssertionException($msg);
            }
        }

        public function assertNull($actual, $message = '') {
            $this->assertionCount++;
            if ($actual !== null) {
                $msg = $message ?: "Failed asserting that " . var_export($actual, true) . " is null.";
                throw new AssertionException($msg);
            }
        }

        public function assertNotNull($actual, $message = '') {
            $this->assertionCount++;
            if ($actual === null) {
                $msg = $message ?: "Failed asserting that value is not null.";
                throw new AssertionException($msg);
            }
        }

        public function assertStringContainsString($needle, $haystack, $message = '') {
            $this->assertionCount++;
            if (strpos($haystack, $needle) === false) {
                $msg = $message ?: "Failed asserting that '{$haystack}' contains '{$needle}'.";
                throw new AssertionException($msg);
            }
        }

        public function assertStringNotContainsString($needle, $haystack, $message = '') {
            $this->assertionCount++;
            if (strpos($haystack, $needle) !== false) {
                $msg = $message ?: "Failed asserting that '{$haystack}' does not contain '{$needle}'.";
                throw new AssertionException($msg);
            }
        }

        public function assertCount($expectedCount, $countable, $message = '') {
            $this->assertionCount++;
            $actualCount = is_array($countable) || $countable instanceof \Countable ? count($countable) : 0;
            if ($actualCount !== $expectedCount) {
                $msg = $message ?: "Failed asserting that count {$actualCount} matches expected count {$expectedCount}.";
                throw new AssertionException($msg);
            }
        }

        public function assertArrayHasKey($key, $array, $message = '') {
            $this->assertionCount++;
            if (!is_array($array) || !array_key_exists($key, $array)) {
                $msg = $message ?: "Failed asserting that array has key '{$key}'.";
                throw new AssertionException($msg);
            }
        }

        public function assertMatchesRegularExpression($pattern, $string, $message = '') {
            $this->assertionCount++;
            if (!preg_match($pattern, $string)) {
                $msg = $message ?: "Failed asserting that '{$string}' matches pattern '{$pattern}'.";
                throw new AssertionException($msg);
            }
        }
    }
}

abstract class TestCase extends BaseTestCase {}

