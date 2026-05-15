<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use Mossetc\TechnicalTest\Shop\Application\DTO\PaginatedShops;
use PHPUnit\Framework\TestCase;

final class PaginatedShopsTest extends TestCase
{
    public function testPagesRoundsUp(): void
    {
        self::assertSame(3, new PaginatedShops([], 25, 1, 10)->pages());
    }

    public function testPagesWhenExactlyDivisible(): void
    {
        self::assertSame(2, new PaginatedShops([], 20, 1, 10)->pages());
    }

    public function testPagesWhenTotalIsZero(): void
    {
        self::assertSame(0, new PaginatedShops([], 0, 1, 10)->pages());
    }

    public function testPagesWhenLimitIsZero(): void
    {
        self::assertSame(0, new PaginatedShops([], 10, 1, 0)->pages());
    }
}
