<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use PHPUnit\Framework\TestCase;

final class UserScopeTest extends TestCase
{
    public function testAllScopeIsAll(): void
    {
        $scope = UserScope::all();

        $this->assertTrue($scope->isAll());
        $this->assertFalse($scope->isCompanies());
        $this->assertFalse($scope->isShops());
        $this->assertSame([], $scope->ids);
        $this->assertNull($scope->scopeCompanyId);
    }

    public function testCompaniesScopeHoldsIds(): void
    {
        $ids   = ['aaa', 'bbb'];
        $scope = UserScope::companies($ids);

        $this->assertFalse($scope->isAll());
        $this->assertTrue($scope->isCompanies());
        $this->assertFalse($scope->isShops());
        $this->assertSame($ids, $scope->ids);
        $this->assertNull($scope->scopeCompanyId);
    }

    public function testShopsScopeHoldsIds(): void
    {
        $ids   = ['shop1'];
        $scope = UserScope::shops($ids);

        $this->assertFalse($scope->isAll());
        $this->assertFalse($scope->isCompanies());
        $this->assertTrue($scope->isShops());
        $this->assertSame($ids, $scope->ids);
        $this->assertNull($scope->scopeCompanyId);
    }

    public function testShopsScopeWithScopeCompanyId(): void
    {
        $scope = UserScope::shops(['shop1'], 'company-x');

        $this->assertTrue($scope->isShops());
        $this->assertSame('company-x', $scope->scopeCompanyId);
    }

    public function testDefaultConstructorProducesAllScope(): void
    {
        $scope = new UserScope();

        $this->assertTrue($scope->isAll());
    }
}
