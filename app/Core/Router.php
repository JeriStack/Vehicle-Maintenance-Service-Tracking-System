<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Class Router
 *
 * Lightweight, robust application router supporting HTTP methods,
 * dynamic URL parameters ({param}), middleware pipeline, and standard error statuses.
 */
class Router
{
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register a route responding to any HTTP verb.
     */
    public function any(string $path, callable|array $handler, array $middleware = []): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $verb) {
            $this->addRoute($verb, $path, $handler, $middleware);
        }
        return $this;
    }

    /**
     * Internal route registration.
     */
    private function addRoute(string $method, string $path, callable|array $handler, array $middleware = []): self
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath === '//') {
            $normalizedPath = '/';
        }

        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $normalizedPath,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /**
     * Dispatch an incoming request through matching routes and middleware.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $pathMatched = false;

        foreach ($this->routes as $route) {
            $pattern = $this->compilePattern($route['path']);

            if (preg_match($pattern, $path, $matches)) {
                $pathMatched = true;

                if ($route['method'] === $method) {
                    // Extract named parameters
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    // Execute middleware chain
                    foreach ($route['middleware'] as $mw) {
                        $mwResponse = $this->resolveMiddleware($mw, $request);
                        if ($mwResponse instanceof Response) {
                            return $mwResponse;
                        }
                    }

                    // Execute handler
                    return $this->resolveHandler($route['handler'], $request, $params);
                }
            }
        }

        // Return 405 if path exists under another method, otherwise 404
        if ($pathMatched) {
            return Response::json([
                'error' => 'Method Not Allowed',
                'message' => "The {$method} method is not supported for route '{$path}'."
            ], 405);
        }

        return Response::json([
            'error' => 'Not Found',
            'message' => "The requested route '{$path}' was not found on this server."
        ], 404);
    }

    /**
     * Compile route pattern with parameters into a PCRE regex.
     * e.g. /vehicles/{id} -> #^/vehicles/(?P<id>[^/]+)$#
     */
    private function compilePattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Execute a middleware instance or callable.
     */
    private function resolveMiddleware(mixed $mw, Request $request): ?Response
    {
        if (is_callable($mw)) {
            return $mw($request);
        }

        if (is_string($mw) && class_exists($mw)) {
            $instance = new $mw();
            if (method_exists($instance, 'handle')) {
                return $instance->handle($request);
            }
        }

        return null;
    }

    /**
     * Execute route handler and return a Response instance.
     */
    private function resolveHandler(callable|array $handler, Request $request, array $params): Response
    {
        if (is_callable($handler)) {
            $result = $handler($request, ...array_values($params));
        } elseif (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = is_string($class) ? new $class() : $class;
            $result = $instance->$method($request, ...array_values($params));
        } else {
            return Response::json(['error' => 'Invalid Route Handler'], 500);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    /**
     * Return registered routes for introspection.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
