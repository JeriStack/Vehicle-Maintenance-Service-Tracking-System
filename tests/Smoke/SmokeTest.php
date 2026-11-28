<?php
declare(strict_types=1);

namespace Tests\Smoke;

use Tests\BaseTestCase;
use VMS\Core\App;
use VMS\Core\Config;
use VMS\Core\Csrf;
use VMS\Core\Database;
use VMS\Core\Env;
use VMS\Core\MigrationRunner;
use VMS\Core\Request;
use VMS\Core\Response;
use VMS\Core\Router;
use VMS\Core\Session;

/**
 * Class SmokeTest
 *
 * Automated Smoke Test Suite verifying Phase 4 application foundation:
 * bootstrap, autoloading, configuration, routing, health endpoints, CSRF, and sessions.
 */
class SmokeTest extends BaseTestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = dirname(__DIR__, 2);
        Config::boot($this->basePath);
    }

    protected function tearDown(): void
    {
        @restore_error_handler();
        @restore_exception_handler();
        parent::tearDown();
    }

    /**
     * 1. Test Application Bootstrap
     */
    public function testApplicationBootstrap(): void
    {
        $app = new App($this->basePath);
        $this->assertTrue($app instanceof App, "App should instantiate successfully");
        $this->assertTrue(is_string($app->getBasePath()), "App should expose base path");
        $this->assertTrue($app->getRouter() instanceof Router, "App should provide Router instance");
    }

    /**
     * 2. Test Composer Autoloading of Core Namespaces
     */
    public function testComposerAutoloading(): void
    {
        $this->assertTrue(class_exists(App::class), "App class should be autoloadable");
        $this->assertTrue(class_exists(Config::class), "Config class should be autoloadable");
        $this->assertTrue(class_exists(Database::class), "Database class should be autoloadable");
        $this->assertTrue(class_exists(Csrf::class), "Csrf class should be autoloadable");
        $this->assertTrue(class_exists(Session::class), "Session class should be autoloadable");
        $this->assertTrue(class_exists(Router::class), "Router class should be autoloadable");
        $this->assertTrue(class_exists(Request::class), "Request class should be autoloadable");
        $this->assertTrue(class_exists(Response::class), "Response class should be autoloadable");
    }

    /**
     * 3. Test Configuration and Environment Loading
     */
    public function testConfigurationLoading(): void
    {
        $appName = Config::get('app.name');
        $this->assertTrue(is_string($appName) && strlen($appName) > 0, "App name should be configured");

        $charset = Config::get('database.charset');
        $this->assertTrue($charset === 'utf8mb4', "Database charset should default to utf8mb4");

        $port = Config::get('database.port');
        $this->assertTrue(is_int($port) && $port > 0, "Database port should be a positive integer");
    }

    /**
     * 4. Test Health Endpoint Liveness (GET /health)
     */
    public function testHealthEndpointLiveness(): void
    {
        $app = new App($this->basePath);
        $router = $app->getRouter();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/health',
        ]);

        $response = $router->dispatch($request);
        $this->assertTrue($response->getStatusCode() === 200, "Health probe should return HTTP 200");

        $data = json_decode($response->getContent(), true);
        $this->assertTrue(is_array($data), "Response should be valid JSON");
        $this->assertTrue(($data['status'] ?? '') === 'ok', "Health status must be 'ok'");
        $this->assertTrue(isset($data['timestamp']), "Health response must contain timestamp");
    }

    /**
     * 5. Test Health Readiness Endpoint (GET /health/ready)
     */
    public function testHealthReadinessEndpoint(): void
    {
        $app = new App($this->basePath);
        $router = $app->getRouter();

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/health/ready',
        ]);

        $response = $router->dispatch($request);
        $statusCode = $response->getStatusCode();
        $this->assertTrue(in_array($statusCode, [200, 503], true), "Readiness must return 200 or 503 depending on DB");

        $data = json_decode($response->getContent(), true);
        $this->assertTrue(isset($data['status']), "Readiness payload should have 'status'");
        $this->assertTrue(isset($data['database']), "Readiness payload should have 'database'");
    }

    /**
     * 6. Test Router Parameter Matching and Execution
     */
    public function testRouterParameterMatching(): void
    {
        $router = new Router();
        $router->get('/vehicles/{id}/details', function (Request $req, string $id) {
            return Response::json(['vehicle_id' => (int) $id]);
        });

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/vehicles/42/details',
        ]);

        $response = $router->dispatch($request);
        $this->assertTrue($response->getStatusCode() === 200, "Route parameter dispatch should succeed");

        $data = json_decode($response->getContent(), true);
        $this->assertTrue(($data['vehicle_id'] ?? 0) === 42, "Named parameter {id} should resolve to 42");
    }

    /**
     * 7. Test Router 404 Not Found
     */
    public function testRouterNotFound404(): void
    {
        $router = new Router();
        $request = new Request([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/non-existent-route-12345',
        ]);

        $response = $router->dispatch($request);
        $this->assertTrue($response->getStatusCode() === 404, "Unknown route should return HTTP 404");
    }

    /**
     * 8. Test Router 405 Method Not Allowed
     */
    public function testRouterMethodNotAllowed405(): void
    {
        $router = new Router();
        $router->get('/only-get-route', function () {
            return Response::html("OK");
        });

        $request = new Request([], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI'    => '/only-get-route',
        ]);

        $response = $router->dispatch($request);
        $this->assertTrue($response->getStatusCode() === 405, "Mismatched verb should return HTTP 405");
    }

    /**
     * 9. Test CSRF Token Generation and Validation
     */
    public function testCsrfGenerationAndValidation(): void
    {
        $token = Csrf::token();
        $this->assertTrue(is_string($token) && strlen($token) === 64, "CSRF token must be 64-character hex");

        $this->assertTrue(Csrf::validate($token), "Valid token must pass constant-time comparison");
        $this->assertFalse(Csrf::validate("tampered_token_string"), "Invalid token must be rejected");
        $this->assertFalse(Csrf::validate(null), "Null token must be rejected");
        $this->assertFalse(Csrf::validate(""), "Empty token must be rejected");

        $field = Csrf::field();
        $this->assertTrue(str_contains($field, 'type="hidden"'), "Csrf::field() must render hidden input");
        $this->assertTrue(str_contains($field, 'name="csrf_token"'), "Csrf::field() must have name='csrf_token'");
        $this->assertTrue(str_contains($field, $token), "Csrf::field() must embed current token");
    }

    /**
     * 10. Test Session Bootstrap and Flash Messages
     */
    public function testSessionBootstrapAndFlashMessages(): void
    {
        Session::start();
        $this->assertTrue(session_status() === PHP_SESSION_ACTIVE, "Session must be active");

        Session::flash('smoke_test_notice', 'Temporary message');
        $this->assertTrue(Session::hasFlash('smoke_test_notice'), "Flash message should be present");

        $retrieved = Session::getFlash('smoke_test_notice');
        $this->assertTrue($retrieved === 'Temporary message', "Flash value should match");
        $this->assertFalse(Session::hasFlash('smoke_test_notice'), "Flash message must be consumed on retrieval");
    }

    /**
     * 11. Test Form Helpers and HTML Escaping
     */
    public function testFormHelpersAndHtmlEscaping(): void
    {
        $dangerous = '<script>alert("XSS")</script>&foo="bar"';
        $escaped = e($dangerous);

        $this->assertFalse(str_contains($escaped, '<script>'), "e() must escape opening script tag");
        $this->assertTrue(str_contains($escaped, '&lt;script&gt;'), "e() must convert to HTML entities");
        $this->assertTrue(str_contains($escaped, '&quot;'), "e() must escape double quotes");

        $this->assertTrue(is_string(csrf_token()) && strlen(csrf_token()) === 64, "csrf_token() helper should work");
        $this->assertTrue(str_contains(csrf_field(), 'name="csrf_token"'), "csrf_field() helper should work");
    }

    /**
     * 12. Test Migration Runner Discovery and Status
     */
    public function testMigrationRunnerDiscoveryAndStatus(): void
    {
        // Instantiating MigrationRunner without a live DB can be tested via mock or checking file detection
        $migrationsDir = $this->basePath . '/database/migrations';
        $files = glob($migrationsDir . '/*.sql');
        $this->assertTrue(is_array($files) && count($files) > 0, "Migration directory should contain .sql files");
    }

    /**
     * 13. Test Directory Structure, Access Controls, and Asset Parity
     */
    public function testDirectoryStructureAndAccessControls(): void
    {
        $requiredDirs = [
            'public',
            'app/Core',
            'app/Controllers',
            'app/Middleware',
            'app/Security',
            'bin',
            'config',
            'database/migrations',
            'database/seeders',
            'logs',
            'storage/logs',
            'storage/sessions',
            'storage/cache',
            'storage/uploads',
            'storage/backups',
            'uploads/vehicle_documents',
        ];

        foreach ($requiredDirs as $dir) {
            $this->assertTrue(is_dir($this->basePath . '/' . $dir), "Required directory '{$dir}' must exist");
        }

        $protectedDirs = [
            'app',
            'bin',
            'config',
            'database',
            'docs',
            'helpers',
            'includes',
            'logs',
            'storage',
            'tests',
            'uploads',
            'websocket',
        ];

        foreach ($protectedDirs as $dir) {
            $htaccess = $this->basePath . '/' . $dir . '/.htaccess';
            $this->assertTrue(file_exists($htaccess), "Protected directory '{$dir}' must contain .htaccess");
        }

        $assetsToSync = [
            'css/style.css',
            'js/password-toggle.js',
        ];

        foreach ($assetsToSync as $asset) {
            $rootFile = $this->basePath . '/assets/' . $asset;
            $publicFile = $this->basePath . '/public/assets/' . $asset;
            $this->assertTrue(file_exists($publicFile), "Public asset '{$asset}' must exist");
            $this->assertSame(md5_file($rootFile), md5_file($publicFile), "Asset '{$asset}' must stay synced between assets/ and public/assets/");
        }
    }
}
