<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use VMS\Core\Csrf;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;
use VMS\Middleware\ValidateCsrfToken;

/**
 * Class CsrfMiddlewareTest
 *
 * Verifies CSRF protection middleware on safe vs state-changing HTTP methods.
 */
class CsrfMiddlewareTest extends TestCase
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

    public function testSafeMethodsBypassCsrfValidation(): void
    {
        $mw = new ValidateCsrfToken();

        foreach (['GET', 'HEAD', 'OPTIONS'] as $method) {
            $request = new Request([], [], ['REQUEST_METHOD' => $method, 'REQUEST_URI' => '/vehicles']);
            $this->assertNull($mw->handle($request));
        }
    }

    public function testStateChangingMethodsRejectMissingToken(): void
    {
        $mw = new ValidateCsrfToken();

        foreach (['POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $request = new Request([], [], [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI'    => '/api/vehicles',
                'HTTP_ACCEPT'    => 'application/json',
            ]);

            $response = $mw->handle($request);
            $this->assertTrue($response instanceof Response);
            $this->assertEquals(403, $response->getStatusCode());
        }
    }

    public function testStateChangingMethodsRejectInvalidToken(): void
    {
        Csrf::token(); // Generate active token
        $mw = new ValidateCsrfToken();

        $request = new Request(['csrf_token' => 'invalid-token-12345'], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/api/vehicles',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $response = $mw->handle($request);
        $this->assertTrue($response instanceof Response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testStateChangingMethodsAcceptValidToken(): void
    {
        $validToken = Csrf::token();
        $mw = new ValidateCsrfToken();

        $request = new Request(['csrf_token' => $validToken], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/api/vehicles',
            'HTTP_ACCEPT'    => 'application/json',
        ]);

        $this->assertNull($mw->handle($request));
    }

    public function testLegacySessionCsrfTokenSynchronization(): void
    {
        Session::start();
        $legacyToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $legacyToken;

        $this->assertTrue(Csrf::validate($legacyToken), 'Legacy $_SESSION[csrf_token] must validate');
        $this->assertSame($legacyToken, Csrf::token(), 'Csrf::token() must adopt existing legacy token');

        $rotated = Csrf::rotate();
        $this->assertSame($rotated, $_SESSION['csrf_token'], 'Csrf::rotate() must update legacy $_SESSION[csrf_token]');
    }
}
