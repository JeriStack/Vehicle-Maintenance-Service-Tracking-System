<?php
declare(strict_types=1);

namespace VMS\Controllers;

use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Database;

/**
 * Class HealthController
 *
 * Provides non-sensitive liveness and readiness monitoring endpoints.
 */
class HealthController
{
    /**
     * Application liveness probe (GET /health).
     * Returns generic health status without disclosing internal infrastructure.
     */
    public function check(Request $request): Response
    {
        return Response::json([
            'status' => 'ok',
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Application readiness probe (GET /health/ready).
     * Verifies critical backend services (e.g. database connectivity).
     */
    public function readiness(Request $request): Response
    {
        $dbReady = Database::testConnection();

        $statusCode = $dbReady ? 200 : 503;

        return Response::json([
            'status' => $dbReady ? 'ready' : 'degraded',
            'database' => $dbReady ? 'connected' : 'unavailable',
            'timestamp' => date('c'),
        ], $statusCode);
    }
}
