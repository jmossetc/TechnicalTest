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
        self::assertSame(ShopSortField::Name, $criteria->field);
    }

    public function testDefaultDirectionIsAsc(): void
    {
        $criteria = new ShopSortCriteria();
        self::assertSame(SortDirection::Asc, $criteria->direction);
    }

    public function testAllFieldsAreValidEnumValues(): void
    {
        self::assertSame('company_id', ShopSortField::CompanyId->value);
        self::assertSame('name', ShopSortField::Name->value);
        self::assertSame('email', ShopSortField::Email->value);
        self::assertSame('city', ShopSortField::City->value);
        self::assertSame('postal_code', ShopSortField::PostalCode->value);
        self::assertSame('country', ShopSortField::Country->value);
        self::assertSame('is_active', ShopSortField::IsActive->value);
        self::assertSame('created_at', ShopSortField::CreatedAt->value);
        self::assertSame('updated_at', ShopSortField::UpdatedAt->value);
    }

    public function testAcceptsAllDirections(): void
    {
        $asc  = new ShopSortCriteria(direction: SortDirection::Asc);
        $desc = new ShopSortCriteria(direction: SortDirection::Desc);

        self::assertSame(SortDirection::Asc, $asc->direction);
        self::assertSame(SortDirection::Desc, $desc->direction);
    }

    public function testAcceptsCustomField(): void
    {
        $criteria = new ShopSortCriteria(field: ShopSortField::CreatedAt);
        self::assertSame(ShopSortField::CreatedAt, $criteria->field);
    }
}
