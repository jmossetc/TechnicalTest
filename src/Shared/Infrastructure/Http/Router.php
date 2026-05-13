<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Http;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

final readonly class Router
{
    public function __construct(
        private RouteCollection $routes,
        private ContainerInterface $controllers,
    ) {}

    public function dispatch(Request $request): Response
    {
        $context = new RequestContext(method: $request->method, path: $request->path);
        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $match = $matcher->match($request->path);
        } catch (ResourceNotFoundException) {
            return Response::error('Not Found', 404);
        } catch (MethodNotAllowedException $e) {
            return Response::error(
                sprintf('Method Not Allowed. Allowed: %s', implode(', ', $e->getAllowedMethods())),
                405,
            );
        }

        $routeName = is_string($match['_route'] ?? null) ? $match['_route'] : '';

        if (!$this->controllers->has($routeName)) {
            return Response::error('Not Found', 404);
        }

        $controller = $this->controllers->get($routeName);

        if (!$controller instanceof ControllerInterface) {
            return Response::error('Not Found', 404);
        }

        $attributes = [];
        foreach ($match as $key => $value) {
            if (!str_starts_with($key, '_') && is_string($value)) {
                $attributes[$key] = $value;
            }
        }

        return $controller($request->withAttributes($attributes));
    }
}
