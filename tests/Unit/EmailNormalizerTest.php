<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Security\EmailNormalizer;

/**
 * Class EmailNormalizerTest
 *
 * Verifies canonical email normalization and format validation.
 */
class EmailNormalizerTest extends TestCase
{
    public function testCanonicalLowercaseNormalization(): void
    {
        $input = "  User.Name+Tag@Example.COM  ";
        $normalized = EmailNormalizer::normalize($input);

        $this->assertEquals("user.name+tag@example.com", $normalized);
    }

    public function testNullAndEmptyInput(): void
    {
        $this->assertEquals("", EmailNormalizer::normalize(null));
        $this->assertEquals("", EmailNormalizer::normalize("   "));
    }

    public function testControlCharacterStripping(): void
    {
        $input = "user\x00\x1F@example.com";
        $normalized = EmailNormalizer::normalize($input);

        $this->assertEquals("user@example.com", $normalized);
    }

    public function testValidEmailValidation(): void
    {
        $this->assertTrue(EmailNormalizer::isValid("admin@example.com"));
        $this->assertTrue(EmailNormalizer::isValid("user.test+filter@sub.domain.org"));
    }

    public function testInvalidEmailValidation(): void
    {
        $this->assertFalse(EmailNormalizer::isValid("plainaddress"));
        $this->assertFalse(EmailNormalizer::isValid("@missingusername.com"));
        $this->assertFalse(EmailNormalizer::isValid("username@.com"));
        $this->assertFalse(EmailNormalizer::isValid(null));
    }
}
