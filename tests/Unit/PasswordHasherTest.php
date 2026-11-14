<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Security\PasswordHasher;

/**
 * Class PasswordHasherTest
 *
 * Verifies cryptographic password hashing, verification, rehashing, and policy validation.
 */
class PasswordHasherTest extends TestCase
{
    public function testHashAndVerificationSuccess(): void
    {
        $plain = "SecurePass123!";
        $hash = PasswordHasher::hash($plain);

        $this->assertTrue(str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2'));
        $this->assertTrue(PasswordHasher::verify($plain, $hash));
    }

    public function testVerificationFailureOnWrongPassword(): void
    {
        $hash = PasswordHasher::hash("CorrectPassword");

        $this->assertFalse(PasswordHasher::verify("WrongPassword", $hash));
        $this->assertFalse(PasswordHasher::verify("", $hash));
        $this->assertFalse(PasswordHasher::verify("CorrectPassword", ""));
    }

    public function testNeedsRehashDetection(): void
    {
        $hash = PasswordHasher::hash("TestPassword");
        // Fresh hash with current default settings should not need rehash
        $this->assertFalse(PasswordHasher::needsRehash($hash));
    }

    public function testPolicyValidation(): void
    {
        // Valid password
        $errors = PasswordHasher::validatePolicy("validPass123", "validPass123");
        $this->assertEquals(0, count($errors));

        // Too short (< 8 chars)
        $shortErrors = PasswordHasher::validatePolicy("short", "short");
        $this->assertTrue(count($shortErrors) > 0);

        // Mismatched confirmation
        $mismatchErrors = PasswordHasher::validatePolicy("validPass123", "differentPass123");
        $this->assertTrue(count($mismatchErrors) > 0);
    }
}
