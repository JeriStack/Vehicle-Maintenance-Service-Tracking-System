<?php
// tests/Unit/RememberMeTest.php
namespace Tests\Unit;

use Tests\TestCase;

class RememberMeTest extends TestCase {

    public function testTokenFormatAndEntropy() {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        // 16 bytes = 32 hex chars
        $this->assertEquals(32, strlen($selector));
        // 32 bytes = 64 hex chars
        $this->assertEquals(64, strlen($validator));

        $cookieValue = $selector . ':' . $validator;
        $parts = explode(':', $cookieValue);

        $this->assertCount(2, $parts);
        $this->assertEquals($selector, $parts[0]);
        $this->assertEquals($validator, $parts[1]);
    }

    public function testHashEqualsValidation() {
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);

        // Valid validator must match
        $incomingHashed = hash('sha256', $validator);
        $this->assertTrue(hash_equals($hashedValidator, $incomingHashed));

        // Tampered validator must fail
        $tamperedValidator = bin2hex(random_bytes(32));
        $tamperedHashed = hash('sha256', $tamperedValidator);
        $this->assertFalse(hash_equals($hashedValidator, $tamperedHashed));
    }

    public function testTokenExpirationCalculation() {
        $futureExpires = date('Y-m-d H:i:s', time() + (86400 * 30));
        $this->assertTrue(strtotime($futureExpires) > time());

        $pastExpires = date('Y-m-d H:i:s', time() - 3600);
        $this->assertTrue(strtotime($pastExpires) < time());
    }
}

