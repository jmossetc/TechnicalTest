<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Http;

final readonly class Request
{
    /**
     * @param array<string, string> $headers    HTTP headers keyed by canonical header name
     * @param array<string, mixed>  $body       Decoded JSON body
     * @param array<string, string> $attributes Route parameters injected by the router
     * @param array<string, string> $query      URL query string parameters
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers,
        public array $body,
        public array $attributes = [],
        public array $query = [],
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper(self::serverString('REQUEST_METHOD', 'GET'));

        $uri  = self::serverString('REQUEST_URI', '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name           = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                $headers[$name] = $value;
            }
        }

        $body = self::parseJsonBody();

        $query = [];
        foreach ($_GET as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $query[$key] = $value;
            }
        }

        return new self($method, $path, $headers, $body, query: $query);
    }

    /**
     * Return a new instance with router-matched route parameters added.
     *
     * @param array<string, string> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self($this->method, $this->path, $this->headers, $this->body, $attributes, $this->query);
    }

    /** Read a body key as string, returning $default when absent or non-string. */
    public function stringBody(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    private static function serverString(string $key, string $default = ''): string
    {
        $value = $_SERVER[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /** @return array<string, mixed> */
    private static function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, associative: true);

        if (!is_array($decoded)) {
            return [];
        }

        $body = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $body[$key] = $value;
            }
        }

        return $body;
    }
}
