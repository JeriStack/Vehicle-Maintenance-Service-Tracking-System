<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Core\Config;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;
use VMS\Middleware\RequireAuthentication;
use VMS\Middleware\RequireRole;

/**
 * Class AuthMiddlewareTest
 *
 * Verifies centralized authentication and role-based authorization middleware.
 */
class AuthMiddlewareTest extends TestCase
{
    private array $savedSession;

    protected function setUp(): void
    {
        $this->savedSession = $_SESSION ?? [];
        $_SESSION = [];
        Config::boot(dirname(__DIR__, 2));
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;
    }

    public function testRequireAuthenticationBlocksUnauthenticatedJsonRequest(): void
    {
        $_SESSION = [];
        $mw = new RequireAuthentication();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/vehicles',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $mw->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRequireAuthenticationBlocksExpiredSession(): void
    {
        $_SESSION['user_id'] = 10;
        $_SESSION['last_activity'] = time() - 3600; // 1 hour ago
        $mw = new RequireAuthentication();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/vehicles',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $mw->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRequireRoleRejectsUnauthenticatedUser(): void
    {
        $_SESSION = [];
        $roleMw = RequireRole::admin();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/admin/users',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $roleMw->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRequireRoleRejectsVehicleOwnerFromAdminRoute(): void
    {
        $_SESSION['user_id'] = 5;
        $_SESSION['role'] = 'user'; // Vehicle Owner
        $adminMw = RequireRole::admin();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/admin/system',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $adminMw->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRequireRoleRejectsAdminFromOwnerPrivateRoutes(): void
    {
        $_SESSION['user_id'] = 2;
        $_SESSION['role'] = 'admin'; // Administrator
        $ownerMw = RequireRole::owner();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/my/vehicles',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $ownerMw->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRequireRoleRejectsUnknownRole(): void
    {
        $_SESSION['user_id'] = 7;
        $_SESSION['role'] = 'moderator'; // Unknown role
        $roleMw = RequireRole::owner();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/vehicles',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $roleMw->handle($request);

        $this->assertTrue($response instanceof Response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRequireRoleAllowsAuthorizedRole(): void
    {
        $_SESSION['user_id'] = 2;
        $_SESSION['role'] = 'admin';
        $adminMw = RequireRole::admin();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/api/admin/dashboard',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $adminMw->handle($request);

        // Allowed -> returns null to proceed down pipeline
        $this->assertNull($response);
    }
}
