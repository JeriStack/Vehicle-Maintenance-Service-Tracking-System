<?php
declare(strict_types=1);

namespace VMS\Core;

/**
 * Class Request
 *
 * Encapsulates incoming HTTP request state, headers, method, and parameters.
 */
class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $post;
    private array $server;
    private array $headers;

    public function __construct(
        array $query = [],
        array $post = [],
        array $server = []
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $server['REQUEST_URI'] ?? '/';
        $this->path = $this->resolvePath($this->uri);
        $this->headers = $this->extractHeaders($server);
    }

    /**
     * Factory to instantiate from PHP global state.
     */
    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    /**
     * Resolve a normalized path regardless of subfolder or query string.
     */
    private function resolvePath(string $uri): string
    {
        // Strip query string
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        // Detect script directory prefix (e.g. /Vehicle-Maintenance-Service-Tracking-System/public)
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);

        if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        // Also check if index.php is directly in path
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }

        $path = '/' . ltrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * Extract HTTP headers from $_SERVER.
     */
    private function extractHeaders(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $header = strtolower(str_replace('_', '-', $key));
                $headers[$header] = (string) $value;
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        if (array_key_exists($key, $this->query)) {
            return $this->query[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isAjax(): bool
    {
        return ($this->headers['x-requested-with'] ?? '') === 'XMLHttpRequest' ||
               str_contains($this->headers['accept'] ?? '', 'application/json');
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? $default;
    }
}
