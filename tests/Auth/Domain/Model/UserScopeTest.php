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

        self::assertTrue($scope->isAll());
        self::assertFalse($scope->isCompanies());
        self::assertFalse($scope->isShops());
        self::assertSame([], $scope->ids);
        self::assertNull($scope->scopeCompanyId);
    }

    public function testCompaniesScopeHoldsIds(): void
    {
        $ids   = ['aaa', 'bbb'];
        $scope = UserScope::companies($ids);

        self::assertFalse($scope->isAll());
        self::assertTrue($scope->isCompanies());
        self::assertFalse($scope->isShops());
        self::assertSame($ids, $scope->ids);
        self::assertNull($scope->scopeCompanyId);
    }

    public function testShopsScopeHoldsIds(): void
    {
        $ids   = ['shop1'];
        $scope = UserScope::shops($ids);

        self::assertFalse($scope->isAll());
        self::assertFalse($scope->isCompanies());
        self::assertTrue($scope->isShops());
        self::assertSame($ids, $scope->ids);
        self::assertNull($scope->scopeCompanyId);
    }

    public function testShopsScopeWithScopeCompanyId(): void
    {
        $scope = UserScope::shops(['shop1'], 'company-x');

        self::assertTrue($scope->isShops());
        self::assertSame('company-x', $scope->scopeCompanyId);
    }

    public function testDefaultConstructorProducesAllScope(): void
    {
        $scope = new UserScope();

        self::assertTrue($scope->isAll());
    }
}
