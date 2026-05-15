<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySortCriteria;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use PHPUnit\Framework\TestCase;

final class ListCompaniesCommandTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $cmd = new ListCompanies();

        self::assertSame(1, $cmd->page);
        self::assertSame(10, $cmd->limit);
        self::assertNull($cmd->criteria->name);
        self::assertNull($cmd->criteria->email);
    }

    public function testAcceptsCustomCriteria(): void
    {
        $criteria = new CompanySearchCriteria(name: 'Acme', city: 'Paris');
        $cmd      = new ListCompanies(page: 3, limit: 25, criteria: $criteria);

        self::assertSame(3, $cmd->page);
        self::assertSame(25, $cmd->limit);
        self::assertSame('Acme', $cmd->criteria->name);
        self::assertSame('Paris', $cmd->criteria->city);
    }

    public function testRejectsPageZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(page: 0);
    }

    public function testRejectsNegativePage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(page: -1);
    }

    public function testRejectsLimitZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(limit: 0);
    }

    public function testRejectsLimitAbove100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListCompanies(limit: 101);
    }

    public function testAcceptsLimitBoundaries(): void
    {
        self::assertSame(1, new ListCompanies(limit: 1)->limit);
        self::assertSame(100, new ListCompanies(limit: 100)->limit);
    }

    public function testDefaultSortIsNameAsc(): void
    {
        $cmd = new ListCompanies();

        self::assertSame(CompanySortField::Name, $cmd->sort->field);
        self::assertSame(SortDirection::Asc, $cmd->sort->direction);
    }

    public function testAcceptsCustomSort(): void
    {
        $sort = new CompanySortCriteria(
            field: CompanySortField::CreatedAt,
            direction: SortDirection::Desc,
        );
        $cmd = new ListCompanies(sort: $sort);

        self::assertSame($sort, $cmd->sort);
    }
}
