<?php
declare(strict_types=1);

namespace VMS\Middleware;

use VMS\Core\Csrf;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;

/**
 * Class ValidateCsrfToken
 *
 * Middleware validating cryptographic CSRF tokens on all state-changing HTTP requests.
 */
class ValidateCsrfToken
{
    /** @var array<string> */
    private array $safeMethods = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request): ?Response
    {
        $method = strtoupper($request->getMethod());

        if (in_array($method, $this->safeMethods, true)) {
            return null; // Safe methods do not mutate state
        }

        $token = $request->input('csrf_token');
        if (!$token) {
            $token = $request->server('HTTP_X_CSRF_TOKEN');
        }

        if (!is_string($token) || !Csrf::validate($token)) {
            if ($this->expectsJson($request)) {
                return Response::json([
                    'error' => 'Forbidden',
                    'message' => 'CSRF token mismatch or expired request.',
                ], 403);
            }

            Session::start();
            Session::flash('flash_error', 'Your request could not be validated. Please try again.');
            $referer = $request->server('HTTP_REFERER');
            if ($referer && is_string($referer)) {
                return Response::redirect($referer);
            }

            return Response::html("<h1>403 Forbidden</h1><p>CSRF token validation failed.</p>", 403);
        }

        return null;
    }

    private function expectsJson(Request $request): bool
    {
        $accept = (string) $request->server('HTTP_ACCEPT', '');
        return str_contains($accept, 'application/json') || str_starts_with($request->getPath(), '/api/');
    }
}
