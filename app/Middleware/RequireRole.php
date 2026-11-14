<?php
declare(strict_types=1);

namespace VMS\Middleware;

use VMS\Core\Config;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;

/**
 * Class RequireRole
 *
 * Enforces explicit role-based authorization for protected route groups.
 * Rejects unauthenticated users (401), unauthorized roles (403), and unknown role identifiers.
 */
class RequireRole
{
    /** @var array<string> */
    private array $allowedRoles;

    /**
     * @param array<string> $allowedRoles e.g. ['admin'] or ['user']
     */
    public function __construct(array $allowedRoles = ['admin'])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public static function admin(): self
    {
        return new self(['admin']);
    }

    public static function owner(): self
    {
        return new self(['user']);
    }

    public static function for(array $roles): self
    {
        return new self($roles);
    }

    public function handle(Request $request): ?Response
    {
        Session::start();

        $userId = Session::get('user_id');
        if (!$userId) {
            if ($this->expectsJson($request)) {
                return Response::json(['error' => 'Unauthenticated', 'message' => 'Authentication required.'], 401);
            }
            $loginUrl = rtrim((string) Config::get('app.url'), '/') . '/auth/login.php';
            return Response::redirect($loginUrl);
        }

        $currentRole = (string) Session::get('role', '');

        // Valid application roles are strictly 'admin' and 'user'
        $recognizedRoles = ['admin', 'user'];
        if (!in_array($currentRole, $recognizedRoles, true)) {
            return $this->forbiddenResponse($request, "Access Denied: Unrecognized account role.");
        }

        if (!in_array($currentRole, $this->allowedRoles, true)) {
            $msg = in_array('admin', $this->allowedRoles, true)
                ? "Access Denied: Administrator privileges required."
                : "Access Denied: You do not have permission to access this resource.";
            return $this->forbiddenResponse($request, $msg);
        }

        return null; // Authorized
    }

    private function forbiddenResponse(Request $request, string $message): Response
    {
        if ($this->expectsJson($request)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => $message,
            ], 403);
        }

        Session::flash('flash_error', $message);
        $baseUrl = rtrim((string) Config::get('app.url'), '/');
        $fallback = (Session::get('role') === 'admin')
            ? $baseUrl . '/admin/dashboard.php'
            : $baseUrl . '/dashboard/index.php';

        return Response::redirect($fallback);
    }

    private function expectsJson(Request $request): bool
    {
        $accept = (string) $request->server('HTTP_ACCEPT', '');
        return str_contains($accept, 'application/json') || str_starts_with($request->getPath(), '/api/');
    }
}
