<?php
// tests/Unit/ValidationTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../helpers/validation.php';

class ValidationTest extends TestCase {

    public function testValidEmailAddresses() {
        $this->assertTrue(isValidEmail('admin@example.com'));
        $this->assertTrue(isValidEmail('user.name+tag@domain.co.uk'));
        $this->assertTrue(isValidEmail('support123@sub.service-tracker.org'));
    }

    public function testInvalidEmailAddresses() {
        $this->assertFalse(isValidEmail('plainaddress'));
        $this->assertFalse(isValidEmail('@missingusername.com'));
        $this->assertFalse(isValidEmail('username@.com'));
        $this->assertFalse(isValidEmail(''));
        $this->assertFalse(isValidEmail(null));
        $this->assertFalse(isValidEmail('username@domain..com'));
    }

    public function testStringLengthValidation() {
        $this->assertTrue(isValidLength('ABC-1234', 3, 20));
        $this->assertTrue(isValidLength('Toyota', 1, 50));
        $this->assertTrue(isValidLength(' Exact5 ', 5, 10)); // Trimming should result in 6
        
        // Fails min limit
        $this->assertFalse(isValidLength('', 1, 10));
        $this->assertFalse(isValidLength('   ', 1, 10));
        $this->assertFalse(isValidLength('A', 2, 10));

        // Fails max limit
        $this->assertFalse(isValidLength('VeryLongVINNumberExceedingLimit', 1, 10));
    }

    public function testSanitizeOutput() {
        $raw = '<script>alert("XSS")</script>';
        $sanitized = sanitizeOutput($raw);
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);

        $withQuotes = 'Toyota "Camry" & \'Vios\'';
        $sanitizedQuotes = sanitizeOutput($withQuotes);
        $this->assertStringContainsString('&quot;Camry&quot;', $sanitizedQuotes);
        $this->assertStringContainsString('&#039;Vios&#039;', $sanitizedQuotes);
        $this->assertStringContainsString('&amp;', $sanitizedQuotes);

        // Leading/trailing whitespace should be trimmed
        $this->assertEquals('Trimmed', sanitizeOutput('   Trimmed   '));
    }

    public function testIsPositiveNumber() {
        $this->assertTrue(isPositiveNumber(0));
        $this->assertTrue(isPositiveNumber(150));
        $this->assertTrue(isPositiveNumber(150.75));
        $this->assertTrue(isPositiveNumber('250'));
        $this->assertTrue(isPositiveNumber('0.00'));

        $this->assertFalse(isPositiveNumber(-1));
        $this->assertFalse(isPositiveNumber(-10.5));
        $this->assertFalse(isPositiveNumber('-50'));
        $this->assertFalse(isPositiveNumber('abc'));
        $this->assertFalse(isPositiveNumber(''));
        $this->assertFalse(isPositiveNumber(null));
    }
}

