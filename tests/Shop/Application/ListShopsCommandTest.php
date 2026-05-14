<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use PHPUnit\Framework\TestCase;

final class ListShopsCommandTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $cmd = new ListShops();

        $this->assertSame(1, $cmd->page);
        $this->assertSame(10, $cmd->limit);
        $this->assertNull($cmd->criteria->companyId);
        $this->assertNull($cmd->criteria->name);
    }

    public function testAcceptsCustomCriteria(): void
    {
        $criteria = new ShopSearchCriteria(name: 'Flagship', city: 'Paris');
        $cmd      = new ListShops(page: 2, limit: 20, criteria: $criteria);

        $this->assertSame(2,          $cmd->page);
        $this->assertSame(20,         $cmd->limit);
        $this->assertSame('Flagship', $cmd->criteria->name);
        $this->assertSame('Paris',    $cmd->criteria->city);
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
        $this->assertSame(1,   (new ListShops(limit: 1))->limit);
        $this->assertSame(100, (new ListShops(limit: 100))->limit);
    }

    public function testDefaultSortIsNameAsc(): void
    {
        $cmd = new ListShops();

        $this->assertSame(\Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortField::Name, $cmd->sort->field);
        $this->assertSame(\Mossetc\TechnicalTest\Shared\Domain\SortDirection::Asc, $cmd->sort->direction);
    }

    public function testAcceptsCustomSort(): void
    {
        $sort = new \Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria(
            field:     \Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortField::CreatedAt,
            direction: \Mossetc\TechnicalTest\Shared\Domain\SortDirection::Desc,
        );
        $cmd = new ListShops(sort: $sort);

        $this->assertSame($sort, $cmd->sort);
    }
}
