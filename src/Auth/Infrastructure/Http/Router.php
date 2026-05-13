<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http;

use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller\GetUserController;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller\LoginUserController;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller\RegisterUserController;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

final class Router
{
    /** @var array<string, ControllerInterface> */
    private readonly array $controllers;

    public function __construct(
        private readonly RouteCollection $routes,
        RegisterUserController $register,
        LoginUserController $login,
        GetUserController $getUser,
    ) {
        $this->controllers = [
            'register' => $register,
            'login'    => $login,
            'get_user' => $getUser,
        ];
    }

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

        $routeName  = is_string($match['_route'] ?? null) ? $match['_route'] : '';
        $controller = $this->controllers[$routeName] ?? null;

        if ($controller === null) {
            return Response::error('Not Found', 404);
        }

        $attributes = [];
        foreach ($match as $key => $value) {
            if (!str_starts_with($key, '_') && is_string($value)) {
                $attributes[$key] = $value;
            }
        }

        return ($controller)($request->withAttributes($attributes));
    }
}
