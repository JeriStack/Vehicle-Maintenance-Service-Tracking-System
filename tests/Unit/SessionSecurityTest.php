<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Core\Config;
use VMS\Core\Session;

/**
 * Class SessionSecurityTest
 *
 * Verifies secure session initialization, idle timeout expiration,
 * auth_version synchronization, and session invalidation.
 */
class SessionSecurityTest extends TestCase
{
    private array $savedSession;

    protected function setUp(): void
    {
        $this->savedSession = $_SESSION ?? [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;
    }

    public function testIdleTimeoutWithinLimit(): void
    {
        $_SESSION['last_activity'] = time() - 300; // 5 minutes ago
        $this->assertTrue(Session::checkIdleTimeout(1800)); // 30 min threshold
    }

    public function testIdleTimeoutExceeded(): void
    {
        $_SESSION['last_activity'] = time() - 2000; // > 30 minutes ago
        $this->assertFalse(Session::checkIdleTimeout(1800));
    }

    public function testRecordActivityUpdatesTimestamp(): void
    {
        $before = time() - 10;
        $_SESSION['last_activity'] = $before;

        Session::recordActivity();
        $this->assertTrue($_SESSION['last_activity'] >= $before + 10);
    }

    public function testAuthVersionSynchronization(): void
    {
        $_SESSION['auth_version'] = 2;

        $this->assertTrue(Session::isAuthVersionValid(2));
        $this->assertFalse(Session::isAuthVersionValid(3)); // DB incremented -> session rejected
    }

    public function testSessionDestroyClearsSessionData(): void
    {
        $_SESSION['user_id'] = 99;
        $_SESSION['role'] = 'user';

        Session::destroy();

        $this->assertFalse(isset($_SESSION['user_id']));
        $this->assertFalse(isset($_SESSION['role']));
    }
}
