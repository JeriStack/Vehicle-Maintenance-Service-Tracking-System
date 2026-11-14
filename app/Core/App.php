<?php
declare(strict_types=1);

namespace VMS\Core;

use Throwable;
use VMS\Controllers\HealthController;

/**
 * Class App
 *
 * Application bootstrap coordinator, error handler, and front controller runner.
 */
class App
{
    private string $basePath;
    private Router $router;
    private static ?self $instance = null;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->router = new Router();
        self::$instance = $this;

        $this->bootstrap();
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Bootstrap environment, configuration, logging, sessions, and routes.
     */
    private function bootstrap(): void
    {
        // 1. Initialize Configuration
        Config::boot($this->basePath);

        // 2. Register Centralized Error & Exception Handling
        $this->registerErrorHandling();

        // 3. Register Core Application Routes
        $this->registerRoutes();
    }

    /**
     * Register production-safe centralized error handling.
     */
    private function registerErrorHandling(): void
    {
        $debug = (bool) Config::get('app.debug', false);

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
        }

        set_exception_handler(function (Throwable $e) use ($debug) {
            $requestId = bin2hex(random_bytes(8));
            $logPath = $this->basePath . '/storage/logs/error.log';

            $logEntry = sprintf(
                "[%s] [CRITICAL] RequestID: %s | Exception: %s in %s:%d\nStack Trace:\n%s\n\n",
                date('c'),
                $requestId,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );

            if (is_dir(dirname($logPath)) && is_writable(dirname($logPath))) {
                error_log($logEntry, 3, $logPath);
            }

            if (!headers_sent()) {
                http_response_code(500);
            }

            if ($debug) {
                echo "<h1>500 Internal Server Error</h1>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8') . ":" . $e->getLine() . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "</pre>";
            } else {
                echo json_encode([
                    'error' => 'Internal Server Error',
                    'message' => 'An unexpected system error occurred.',
                    'request_id' => $requestId,
                ]);
            }
            exit(1);
        });
    }

    /**
     * Register core baseline routes.
     */
    private function registerRoutes(): void
    {
        // Health check endpoints
        $this->router->get('/health', [HealthController::class, 'check']);
        $this->router->get('/health/ready', [HealthController::class, 'readiness']);

        // Default root landing route
        $this->router->get('/', function (Request $request) {
            Session::start();
            $baseUrl = rtrim((string) Config::get('app.url'), '/');

            if (Session::has('user_id')) {
                return Response::redirect($baseUrl . '/dashboard/index.php');
            }

            return Response::redirect($baseUrl . '/auth/login.php');
        });
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Run the application front controller lifecycle.
     */
    public function run(?Request $request = null): void
    {
        $req = $request ?? Request::createFromGlobals();
        $response = $this->router->dispatch($req);
        $response->send();
    }
}
