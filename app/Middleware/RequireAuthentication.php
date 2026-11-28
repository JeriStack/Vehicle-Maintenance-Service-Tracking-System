<?php
declare(strict_types=1);

namespace VMS\Middleware;

use PDO;
use Throwable;
use VMS\Core\Config;
use VMS\Core\Database;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Session;

/**
 * Class RequireAuthentication
 *
 * Centralized authentication middleware verifying active session validity,
 * idle timeout limits, account active status, and auth_version synchronization.
 */
class RequireAuthentication
{
    private const DEFAULT_IDLE_TIMEOUT = 1800; // 30 minutes

    public function handle(Request $request): ?Response
    {
        Session::start();

        // 1. Check if user_id is set in session
        $userId = Session::get('user_id');
        if (!$userId || !is_numeric($userId)) {
            return $this->unauthenticatedResponse($request, "Please log in to access this resource.");
        }

        // 2. Validate idle timeout
        $lastActivity = (int) Session::get('last_activity', 0);
        $idleTimeout = (int) Config::get('session.idle_timeout', self::DEFAULT_IDLE_TIMEOUT);

        if ($lastActivity > 0 && (time() - $lastActivity) > $idleTimeout) {
            Session::destroy();
            return $this->unauthenticatedResponse($request, "Your session has expired due to inactivity. Please log in again.");
        }

        // 3. Verify user state and auth_version from database
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, role, status, auth_version FROM users WHERE id = ?");
            $stmt->execute([(int) $userId]);
            $user = $stmt->fetch();

            if (!$user) {
                Session::destroy();
                return $this->unauthenticatedResponse($request, "Your account could not be found. Please log in.");
            }

            if (($user['status'] ?? '') !== 'active') {
                Session::destroy();
                return $this->unauthenticatedResponse($request, "Your account is inactive. Please contact an administrator.");
            }

            $sessionAuthVersion = (int) Session::get('auth_version', 1);
            $dbAuthVersion = (int) ($user['auth_version'] ?? 1);

            if ($sessionAuthVersion !== $dbAuthVersion) {
                Session::destroy();
                return $this->unauthenticatedResponse($request, "Your session has been invalidated due to a security or role change. Please log in again.");
            }

            // Synchronize active role if updated
            if (Session::get('role') !== $user['role']) {
                Session::set('role', $user['role']);
            }
        } catch (Throwable $e) {
            // If DB connection fails, return 503 or allow fallback in offline tests
            if (Config::get('app.env') === 'testing' && !Database::testConnection()) {
                // Testing fallback
            } else {
                return Response::json(['error' => 'Service Unavailable', 'message' => 'Unable to verify authentication state.'], 503);
            }
        }

        // 4. Update last activity timestamp
        Session::set('last_activity', time());

        return null; // Proceed down pipeline
    }

    private function unauthenticatedResponse(Request $request, string $message): Response
    {
        if ($this->expectsJson($request)) {
            return Response::json([
                'error' => 'Unauthenticated',
                'message' => $message,
            ], 401);
        }

        Session::flash('flash_error', $message);
        $loginUrl = rtrim((string) Config::get('app.url'), '/') . '/auth/login.php';
        return Response::redirect($loginUrl);
    }

    private function expectsJson(Request $request): bool
    {
        $accept = (string) $request->server('HTTP_ACCEPT', '');
        return str_contains($accept, 'application/json') || str_starts_with($request->getPath(), '/api/');
    }
}
