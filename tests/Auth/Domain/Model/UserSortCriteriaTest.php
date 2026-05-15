<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use PHPUnit\Framework\TestCase;

final class UserSortCriteriaTest extends TestCase
{
    public function testDefaultFieldIsEmail(): void
    {
        self::assertSame(UserSortField::Email, new UserSortCriteria()->field);
    }

    public function testDefaultDirectionIsAsc(): void
    {
        self::assertSame(SortDirection::Asc, new UserSortCriteria()->direction);
    }

    public function testAcceptsAllFields(): void
    {
        foreach (UserSortField::cases() as $field) {
            self::assertSame($field, new UserSortCriteria(field: $field)->field);
        }
    }

    public function testAcceptsBothDirections(): void
    {
        self::assertSame(SortDirection::Asc, new UserSortCriteria(direction: SortDirection::Asc)->direction);
        self::assertSame(SortDirection::Desc, new UserSortCriteria(direction: SortDirection::Desc)->direction);
    }
}
