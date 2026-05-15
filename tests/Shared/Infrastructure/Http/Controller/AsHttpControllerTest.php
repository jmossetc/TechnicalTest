<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shared\Infrastructure\Http\Controller;

use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use PHPUnit\Framework\TestCase;

final class AsHttpControllerTest extends TestCase
{
    public function testHoldsRouteValue(): void
    {
        $attribute = new AsHttpController('my_route');

        self::assertSame('my_route', $attribute->route);
    }

    public function testIsAnAttribute(): void
    {
        $reflection = new \ReflectionClass(AsHttpController::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        self::assertNotEmpty($attributes);
    }

    public function testTargetsClasses(): void
    {
        $reflection = new \ReflectionClass(AsHttpController::class);
        $attrs      = $reflection->getAttributes(\Attribute::class);
        /** @var \Attribute $meta */
        $meta = $attrs[0]->newInstance();

        self::assertSame(\Attribute::TARGET_CLASS, $meta->flags);
    }

    public function testRouteIsReadonly(): void
    {
        $reflection = new \ReflectionClass(AsHttpController::class);
        $property   = $reflection->getProperty('route');

        self::assertTrue($property->isReadOnly());
    }
}
