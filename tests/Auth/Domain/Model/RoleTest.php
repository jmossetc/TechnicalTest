<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use PHPUnit\Framework\TestCase;
use ValueError;

final class RoleTest extends TestCase
{
    public function testEnumValues(): void
    {
        self::assertSame('admin', Role::Admin->value);
        self::assertSame('company_admin', Role::CompanyAdmin->value);
        self::assertSame('shop_manager', Role::ShopManager->value);
        self::assertSame('employee', Role::Employee->value);
    }

    public function testFromValidString(): void
    {
        self::assertSame(Role::Admin, Role::from('admin'));
        self::assertSame(Role::CompanyAdmin, Role::from('company_admin'));
        self::assertSame(Role::ShopManager, Role::from('shop_manager'));
        self::assertSame(Role::Employee, Role::from('employee'));
    }

    public function testTryFromReturnsNullForUnknownValue(): void
    {
        self::assertNull(Role::tryFrom('company_manager'));
        self::assertNull(Role::tryFrom('unknown'));
        self::assertNull(Role::tryFrom(''));
    }

    public function testFromThrowsForUnknownValue(): void
    {
        $this->expectException(ValueError::class);
        Role::from('company_manager');
    }
}
