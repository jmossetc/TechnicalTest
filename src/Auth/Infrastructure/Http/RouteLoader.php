<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http;

use RuntimeException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

final class RouteLoader
{
    public function load(string $path): RouteCollection
    {
        if (!is_file($path)) {
            throw new RuntimeException("Route config file not found: {$path}");
        }

        $config = Yaml::parseFile($path);

        if (!is_array($config)) {
            throw new RuntimeException("Route config must be a YAML mapping: {$path}");
        }

        $routes = new RouteCollection();

        foreach ($config as $name => $definition) {
            if (!is_string($name) || !is_array($definition)) {
                continue;
            }

            $routePath = $definition['path'] ?? null;

            if (!is_string($routePath)) {
                throw new RuntimeException("Route '{$name}' is missing a 'path' key");
            }

            $methods = [];
            $rawMethods = $definition['methods'] ?? [];

            if (is_array($rawMethods)) {
                foreach ($rawMethods as $method) {
                    if (is_string($method)) {
                        $methods[] = strtoupper($method);
                    }
                }
            }

            $routes->add($name, new Route($routePath, methods: $methods));
        }

        return $routes;
    }
}
