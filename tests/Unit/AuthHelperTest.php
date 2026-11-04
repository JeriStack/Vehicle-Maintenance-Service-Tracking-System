<?php
// tests/Unit/AuthHelperTest.php
namespace Tests\Unit;

use Tests\TestCase;

// Ensure constants and auth are loaded without triggering headers
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../helpers/auth_helper.php';

class AuthHelperTest extends TestCase {

    private $savedSession;

    protected function setUp(): void {
        $this->savedSession = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = $this->savedSession;
    }

    public function testGuestState() {
        $_SESSION = [];
        $this->assertFalse(isLoggedIn());
        $this->assertFalse(isAdmin());
        $this->assertFalse(isUser());
        $this->assertNull(currentUserId());
        $this->assertNull(currentUserRole());
        $this->assertNull(getCurrentUserId());
    }

    public function testStandardUserState() {
        $_SESSION['user_id'] = 42;
        $_SESSION['role'] = 'user';
        $_SESSION['user_name'] = 'John Doe';

        $this->assertTrue(isLoggedIn());
        $this->assertTrue(isUser());
        $this->assertFalse(isAdmin());
        $this->assertEquals(42, currentUserId());
        $this->assertEquals(42, getCurrentUserId());
        $this->assertEquals('user', currentUserRole());
    }

    public function testAdminUserState() {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['user_name'] = 'Super Admin';

        $this->assertTrue(isLoggedIn());
        $this->assertTrue(isAdmin());
        $this->assertFalse(isUser());
        $this->assertEquals(1, currentUserId());
        $this->assertEquals('admin', currentUserRole());
    }
}

