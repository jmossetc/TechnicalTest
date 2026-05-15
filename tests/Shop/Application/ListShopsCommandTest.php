<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortField;
use PHPUnit\Framework\TestCase;

final class ListShopsCommandTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $cmd = new ListShops();

        self::assertSame(1, $cmd->page);
        self::assertSame(10, $cmd->limit);
        self::assertNull($cmd->criteria->companyId);
        self::assertNull($cmd->criteria->name);
    }

    public function testAcceptsCustomCriteria(): void
    {
        $criteria = new ShopSearchCriteria(name: 'Flagship', city: 'Paris');
        $cmd      = new ListShops(page: 2, limit: 20, criteria: $criteria);

        self::assertSame(2, $cmd->page);
        self::assertSame(20, $cmd->limit);
        self::assertSame('Flagship', $cmd->criteria->name);
        self::assertSame('Paris', $cmd->criteria->city);
    }

    public function testRejectsPageZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListShops(page: 0);
    }

    public function testRejectsNegativePage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListShops(page: -1);
    }

    public function testRejectsLimitZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListShops(limit: 0);
    }

    public function testRejectsLimitAbove100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListShops(limit: 101);
    }

    public function testAcceptsLimitBoundaries(): void
    {
        self::assertSame(1, new ListShops(limit: 1)->limit);
        self::assertSame(100, new ListShops(limit: 100)->limit);
    }

    public function testDefaultSortIsNameAsc(): void
    {
        $cmd = new ListShops();

        self::assertSame(ShopSortField::Name, $cmd->sort->field);
        self::assertSame(SortDirection::Asc, $cmd->sort->direction);
    }

    public function testAcceptsCustomSort(): void
    {
        $sort = new ShopSortCriteria(
            field: ShopSortField::CreatedAt,
            direction: SortDirection::Desc,
        );
        $cmd = new ListShops(sort: $sort);

        self::assertSame($sort, $cmd->sort);
    }
}
