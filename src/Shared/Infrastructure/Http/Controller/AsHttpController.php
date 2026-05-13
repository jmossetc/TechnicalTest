<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller;

/**
 * Marks a class as an HTTP controller and binds it to a named route.
 * When autoconfigure is enabled the DI container reads this attribute and
 * adds the 'app.http_controller' tag automatically.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsHttpController
{
    public function __construct(public readonly string $route) {}
}
