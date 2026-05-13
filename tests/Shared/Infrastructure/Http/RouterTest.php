<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Http;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RouterTest extends TestCase
{
    private function makeRoutes(array $definitions = []): RouteCollection
    {
        $collection = new RouteCollection();

        foreach ($definitions as $name => [$path, $methods]) {
            $collection->add($name, new Route($path, methods: $methods));
        }

        return $collection;
    }

    private function makeContainer(array $controllers): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);

        $container->method('has')->willReturnCallback(
            static fn(string $id): bool => isset($controllers[$id]),
        );
        $container->method('get')->willReturnCallback(
            static fn(string $id) => $controllers[$id] ?? null,
        );

        return $container;
    }

    private function makeController(Response $response): ControllerInterface
    {
        return new class ($response) implements ControllerInterface {
            public function __construct(private readonly Response $response) {}
            public function __invoke(Request $request): Response { return $this->response; }
        };
    }

    private function req(string $method, string $path): Request
    {
        return new Request($method, $path, [], []);
    }

    // ── Successful dispatch ───────────────────────────────────────────────────

    public function testDispatchesMatchedRouteToController(): void
    {
        $expected = Response::json(['ok' => true]);
        $router   = new Router(
            $this->makeRoutes(['list_users' => ['/api/users', ['GET']]]),
            $this->makeContainer(['list_users' => $this->makeController($expected)]),
        );

        $response = $router->dispatch($this->req('GET', '/api/users'));

        $this->assertSame(200, $response->status());
        $this->assertSame(['ok' => true], $response->data());
    }

    public function testInjectsRouteAttributesIntoRequest(): void
    {
        $capturedRequest = null;
        $controller      = new class (\stdClass::class) implements ControllerInterface {
            public ?Request $captured = null;
            public function __invoke(Request $request): Response {
                $this->captured = $request;
                return Response::json([]);
            }
        };

        $router = new Router(
            $this->makeRoutes(['get_user' => ['/api/users/{id}', ['GET']]]),
            $this->makeContainer(['get_user' => $controller]),
        );

        $router->dispatch($this->req('GET', '/api/users/abc-123'));

        $this->assertNotNull($controller->captured);
        $this->assertSame('abc-123', $controller->captured->attributes['id'] ?? null);
    }

    public function testDoesNotPassPrivateRouteAttributesToController(): void
    {
        $controller = new class implements ControllerInterface {
            public ?Request $captured = null;
            public function __invoke(Request $request): Response {
                $this->captured = $request;
                return Response::json([]);
            }
        };

        $router = new Router(
            $this->makeRoutes(['get_item' => ['/api/items/{id}', ['GET']]]),
            $this->makeContainer(['get_item' => $controller]),
        );

        $router->dispatch($this->req('GET', '/api/items/42'));

        // Symfony inserts _route, _controller etc. — these must be stripped
        foreach (array_keys($controller->captured->attributes ?? []) as $key) {
            $this->assertStringNotContainsString('_', substr($key, 0, 1));
        }
    }

    // ── 404 paths ─────────────────────────────────────────────────────────────

    public function testReturns404ForUnknownPath(): void
    {
        $router = new Router(
            $this->makeRoutes(['home' => ['/', ['GET']]]),
            $this->makeContainer(['home' => $this->makeController(Response::json([]))]),
        );

        $response = $router->dispatch($this->req('GET', '/api/unknown'));

        $this->assertSame(404, $response->status());
    }

    public function testReturns404WhenRouteMatchesButControllerNotInContainer(): void
    {
        $router = new Router(
            $this->makeRoutes(['orphan' => ['/api/orphan', ['GET']]]),
            $this->makeContainer([]),
        );

        $response = $router->dispatch($this->req('GET', '/api/orphan'));

        $this->assertSame(404, $response->status());
    }

    public function testReturns404WhenContainerEntryIsNotAController(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn(new \stdClass());

        $router = new Router(
            $this->makeRoutes(['bad' => ['/api/bad', ['GET']]]),
            $container,
        );

        $response = $router->dispatch($this->req('GET', '/api/bad'));

        $this->assertSame(404, $response->status());
    }

    // ── 405 path ──────────────────────────────────────────────────────────────

    public function testReturns405ForWrongMethod(): void
    {
        $router = new Router(
            $this->makeRoutes(['create' => ['/api/users', ['POST']]]),
            $this->makeContainer(['create' => $this->makeController(Response::json([]))]),
        );

        $response = $router->dispatch($this->req('DELETE', '/api/users'));

        $this->assertSame(405, $response->status());
    }

    public function testMethodNotAllowedResponseMentionsAllowedMethods(): void
    {
        $router = new Router(
            $this->makeRoutes(['users' => ['/api/users', ['GET', 'POST']]]),
            $this->makeContainer(['users' => $this->makeController(Response::json([]))]),
        );

        $response = $router->dispatch($this->req('DELETE', '/api/users'));

        $error = $response->data()['error'] ?? '';
        $this->assertStringContainsString('Allowed', $error);
    }
}
