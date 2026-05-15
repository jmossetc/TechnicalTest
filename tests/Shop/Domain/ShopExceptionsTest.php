<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain;

use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use PHPUnit\Framework\TestCase;

final class ShopExceptionsTest extends TestCase
{
    public function testShopAlreadyExistsMessageIncludesName(): void
    {
        $e = new ShopAlreadyExistsException(new ShopName('My Shop'));
        self::assertStringContainsString('My Shop', $e->getMessage());
    }

    public function testShopNotFoundMessageIncludesId(): void
    {
        $e = new ShopNotFoundException('abc-123');
        self::assertStringContainsString('abc-123', $e->getMessage());
    }
}
