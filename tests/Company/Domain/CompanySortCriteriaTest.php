<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Domain;

use Mossetc\TechnicalTest\Company\Domain\Model\CompanySortCriteria;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use PHPUnit\Framework\TestCase;

final class CompanySortCriteriaTest extends TestCase
{
    public function testDefaultFieldIsName(): void
    {
        $this->assertSame(CompanySortField::Name, (new CompanySortCriteria())->field);
    }

    public function testDefaultDirectionIsAsc(): void
    {
        $this->assertSame(SortDirection::Asc, (new CompanySortCriteria())->direction);
    }

    public function testAcceptsAllFields(): void
    {
        foreach (CompanySortField::cases() as $field) {
            $this->assertSame($field, (new CompanySortCriteria(field: $field))->field);
        }
    }

    public function testAcceptsBothDirections(): void
    {
        $this->assertSame(SortDirection::Asc,  (new CompanySortCriteria(direction: SortDirection::Asc))->direction);
        $this->assertSame(SortDirection::Desc, (new CompanySortCriteria(direction: SortDirection::Desc))->direction);
    }
}
