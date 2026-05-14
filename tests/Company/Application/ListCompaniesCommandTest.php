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

        $this->assertSame(1, $cmd->page);
        $this->assertSame(10, $cmd->limit);
        $this->assertNull($cmd->criteria->name);
        $this->assertNull($cmd->criteria->email);
    }

    public function testAcceptsCustomCriteria(): void
    {
        $criteria = new CompanySearchCriteria(name: 'Acme', city: 'Paris');
        $cmd      = new ListCompanies(page: 3, limit: 25, criteria: $criteria);

        $this->assertSame(3,      $cmd->page);
        $this->assertSame(25,     $cmd->limit);
        $this->assertSame('Acme', $cmd->criteria->name);
        $this->assertSame('Paris', $cmd->criteria->city);
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
        $this->assertSame(1,   (new ListCompanies(limit: 1))->limit);
        $this->assertSame(100, (new ListCompanies(limit: 100))->limit);
    }

    public function testDefaultSortIsNameAsc(): void
    {
        $cmd = new ListCompanies();

        $this->assertSame(\Mossetc\TechnicalTest\Company\Domain\Model\CompanySortField::Name, $cmd->sort->field);
        $this->assertSame(\Mossetc\TechnicalTest\Shared\Domain\SortDirection::Asc, $cmd->sort->direction);
    }

    public function testAcceptsCustomSort(): void
    {
        $sort = new \Mossetc\TechnicalTest\Company\Domain\Model\CompanySortCriteria(
            field:     \Mossetc\TechnicalTest\Company\Domain\Model\CompanySortField::CreatedAt,
            direction: \Mossetc\TechnicalTest\Shared\Domain\SortDirection::Desc,
        );
        $cmd = new ListCompanies(sort: $sort);

        $this->assertSame($sort, $cmd->sort);
    }
}
