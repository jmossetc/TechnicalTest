<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Http;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\RouteLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RouteLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/route_loader_test_' . uniqid();
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    private function writeYaml(string $filename, string $content): string
    {
        $path = $this->tmpDir . '/' . $filename;
        file_put_contents($path, $content);

        return $path;
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function testLoadsRoutesFromValidYaml(): void
    {
        $path = $this->writeYaml('routes.yaml', <<<'YAML'
            list_users:
              path: /api/users
              methods: [GET]
            create_user:
              path: /api/users
              methods: [POST]
            YAML);

        $routes = new RouteLoader()->load($path);

        self::assertNotNull($routes->get('list_users'));
        self::assertNotNull($routes->get('create_user'));
    }

    public function testRoutePathIsCorrect(): void
    {
        $path = $this->writeYaml('routes.yaml', <<<'YAML'
            get_user:
              path: /api/users/{id}
              methods: [GET]
            YAML);

        $route = new RouteLoader()->load($path)->get('get_user');

        self::assertNotNull($route);
        self::assertSame('/api/users/{id}', $route->getPath());
    }

    public function testRouteMethodsAreCaseNormalized(): void
    {
        $path = $this->writeYaml('routes.yaml', <<<'YAML'
            create:
              path: /api/items
              methods: [post, Put]
            YAML);

        $route = new RouteLoader()->load($path)->get('create');

        self::assertNotNull($route);
        self::assertContains('POST', $route->getMethods());
        self::assertContains('PUT', $route->getMethods());
    }

    public function testRouteWithNoMethodsIsAllowed(): void
    {
        $path = $this->writeYaml('routes.yaml', <<<'YAML'
            any_method:
              path: /api/ping
            YAML);

        $routes = new RouteLoader()->load($path);

        self::assertNotNull($routes->get('any_method'));
    }

    public function testEmptyFileProducesNoRoutes(): void
    {
        $path = $this->writeYaml('empty.yaml', '{}');

        $routes = new RouteLoader()->load($path);

        self::assertCount(0, $routes);
    }

    public function testMultipleRoutesAreAllLoaded(): void
    {
        $yaml = '';
        for ($i = 1; $i <= 5; $i++) {
            $yaml .= "route_{$i}:\n  path: /api/r{$i}\n  methods: [GET]\n";
        }

        $path   = $this->writeYaml('many.yaml', $yaml);
        $routes = new RouteLoader()->load($path);

        self::assertCount(5, $routes);
    }

    // ── Error paths ───────────────────────────────────────────────────────────

    public function testThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        new RouteLoader()->load('/nonexistent/routes.yaml');
    }

    public function testYamlListProducesNoRoutes(): void
    {
        // A YAML sequence produces integer keys; those are silently skipped
        $path   = $this->writeYaml('list.yaml', "- item1\n- item2\n");
        $routes = new RouteLoader()->load($path);

        self::assertCount(0, $routes);
    }

    public function testThrowsWhenRouteHasNoPathKey(): void
    {
        $path = $this->writeYaml('nopath.yaml', <<<'YAML'
            broken_route:
              methods: [GET]
            YAML);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/path/i");

        new RouteLoader()->load($path);
    }

    public function testSkipsEntryWithNonStringName(): void
    {
        // YAML arrays produce integer keys — they are silently skipped
        $path = $this->writeYaml('arraykey.yaml', <<<'YAML'
            valid_route:
              path: /api/ok
              methods: [GET]
            YAML);

        $routes = new RouteLoader()->load($path);

        self::assertNotNull($routes->get('valid_route'));
    }
}
