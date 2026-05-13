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
        $this->assertSame('admin',         Role::Admin->value);
        $this->assertSame('company_admin', Role::CompanyAdmin->value);
        $this->assertSame('shop_manager',  Role::ShopManager->value);
        $this->assertSame('employee',      Role::Employee->value);
    }

    public function testFromValidString(): void
    {
        $this->assertSame(Role::Admin,        Role::from('admin'));
        $this->assertSame(Role::CompanyAdmin, Role::from('company_admin'));
        $this->assertSame(Role::ShopManager,  Role::from('shop_manager'));
        $this->assertSame(Role::Employee,     Role::from('employee'));
    }

    public function testTryFromReturnsNullForUnknownValue(): void
    {
        $this->assertNull(Role::tryFrom('company_manager'));
        $this->assertNull(Role::tryFrom('unknown'));
        $this->assertNull(Role::tryFrom(''));
    }

    public function testFromThrowsForUnknownValue(): void
    {
        $this->expectException(ValueError::class);
        Role::from('company_manager');
    }
}
