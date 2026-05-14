<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Domain\Model;

use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use PHPUnit\Framework\TestCase;

final class ShopSortCriteriaTest extends TestCase
{
    public function testDefaultFieldIsName(): void
    {
        $criteria = new ShopSortCriteria();
        $this->assertSame(ShopSortField::Name, $criteria->field);
    }

    public function testDefaultDirectionIsAsc(): void
    {
        $criteria = new ShopSortCriteria();
        $this->assertSame(SortDirection::Asc, $criteria->direction);
    }

    public function testAllFieldsAreValidEnumValues(): void
    {
        $this->assertSame('company_id', ShopSortField::CompanyId->value);
        $this->assertSame('name',       ShopSortField::Name->value);
        $this->assertSame('email',      ShopSortField::Email->value);
        $this->assertSame('city',       ShopSortField::City->value);
        $this->assertSame('postal_code', ShopSortField::PostalCode->value);
        $this->assertSame('country',    ShopSortField::Country->value);
        $this->assertSame('is_active',  ShopSortField::IsActive->value);
        $this->assertSame('created_at', ShopSortField::CreatedAt->value);
        $this->assertSame('updated_at', ShopSortField::UpdatedAt->value);
    }

    public function testAcceptsAllDirections(): void
    {
        $asc  = new ShopSortCriteria(direction: SortDirection::Asc);
        $desc = new ShopSortCriteria(direction: SortDirection::Desc);

        $this->assertSame(SortDirection::Asc,  $asc->direction);
        $this->assertSame(SortDirection::Desc, $desc->direction);
    }

    public function testAcceptsCustomField(): void
    {
        $criteria = new ShopSortCriteria(field: ShopSortField::CreatedAt);
        $this->assertSame(ShopSortField::CreatedAt, $criteria->field);
    }
}
