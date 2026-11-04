<?php
// tests/Unit/PasswordResetTest.php
namespace Tests\Unit;

use Tests\TestCase;

class PasswordResetTest extends TestCase {

    public function testResetTokenGeneration() {
        $plainToken = bin2hex(random_bytes(32));
        $this->assertEquals(64, strlen($plainToken));

        $tokenHash = hash('sha256', $plainToken);
        $this->assertEquals(64, strlen($tokenHash));

        // Deterministic hash matches
        $this->assertEquals(hash('sha256', $plainToken), $tokenHash);
    }

    public function testResetUrlConstruction() {
        $baseUrl = 'http://localhost/Vehicle-Maintenance-Service-Tracking-System/';
        $token = 'abc123xyz456token';
        $email = 'user+test@example.com';

        $url = $baseUrl . "auth/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);

        $this->assertStringContainsString('auth/reset_password.php', $url);
        $this->assertStringContainsString('token=abc123xyz456token', $url);
        $this->assertStringContainsString('email=user%2Btest%40example.com', $url);
    }

    public function testPasswordResetExpiryWindow() {
        $oneHourExpiry = date('Y-m-d H:i:s', time() + 3600);
        $diffMinutes = (strtotime($oneHourExpiry) - time()) / 60;
        
        $this->assertTrue($diffMinutes >= 59 && $diffMinutes <= 61);
    }
}

