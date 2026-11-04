<?php
// tests/Unit/MailHelperTest.php
namespace Tests\Unit;

use Tests\TestCase;

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../helpers/mail_helper.php';

class MailHelperTest extends TestCase {

    public function testSendInLogMode() {
        // MailHelper in fallback/log mode should succeed and log
        $res = \MailHelper::send('user@example.com', 'Test User', 'Test Subject', '<p>Hello</p>');
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('mode', $res);
        $this->assertArrayHasKey('message', $res);
    }

    public function testSendPasswordResetStructure() {
        $resetLink = 'http://localhost/Vehicle-Maintenance-Service-Tracking-System/auth/reset_password.php?token=test_token_123&email=user%40example.com';
        $res = \MailHelper::sendPasswordReset('user@example.com', 'John Owner', $resetLink);
        
        $this->assertTrue($res['success']);
    }

    public function testMailConstantsDefined() {
        $this->assertTrue(defined('MAIL_DRIVER'));
        $this->assertTrue(defined('SMTP_HOST'));
        $this->assertTrue(defined('SMTP_PORT'));
        $this->assertTrue(defined('MAIL_FROM_ADDRESS'));
        $this->assertTrue(defined('MAIL_FROM_NAME'));
    }
}

