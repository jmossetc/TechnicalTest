<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Command;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use PHPUnit\Framework\TestCase;

final class ListUsersCommandTest extends TestCase
{
    public function testDefaultsAreApplied(): void
    {
        $cmd = new ListUsers();

        $this->assertSame(1, $cmd->page);
        $this->assertSame(10, $cmd->limit);
        $this->assertTrue($cmd->scope->isAll());
    }

    public function testAcceptsCustomValues(): void
    {
        $scope = UserScope::companies(['c1']);
        $cmd   = new ListUsers(page: 3, limit: 25, scope: $scope);

        $this->assertSame(3, $cmd->page);
        $this->assertSame(25, $cmd->limit);
        $this->assertSame($scope, $cmd->scope);
    }

    public function testRejectsPageLessThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListUsers(page: 0);
    }

    public function testRejectsNegativePage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListUsers(page: -1);
    }

    public function testRejectsLimitOfZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListUsers(limit: 0);
    }

    public function testRejectsLimitAbove100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListUsers(limit: 101);
    }

    public function testAcceptsLimitOf1(): void
    {
        $cmd = new ListUsers(limit: 1);
        $this->assertSame(1, $cmd->limit);
    }

    public function testAcceptsLimitOf100(): void
    {
        $cmd = new ListUsers(limit: 100);
        $this->assertSame(100, $cmd->limit);
    }

    public function testDefaultCriteriaIsEmpty(): void
    {
        $cmd = new ListUsers();

        $this->assertNull($cmd->criteria->email);
        $this->assertNull($cmd->criteria->role);
        $this->assertNull($cmd->criteria->isActive);
        $this->assertNull($cmd->criteria->createdFrom);
    }

    public function testAcceptsCustomCriteria(): void
    {
        $criteria = new UserSearchCriteria(email: 'ali');
        $cmd      = new ListUsers(criteria: $criteria);

        $this->assertSame($criteria, $cmd->criteria);
    }

    public function testDefaultSortIsEmailAsc(): void
    {
        $cmd = new ListUsers();

        $this->assertSame(\Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField::Email, $cmd->sort->field);
        $this->assertSame(\Mossetc\TechnicalTest\Shared\Domain\SortDirection::Asc, $cmd->sort->direction);
    }

    public function testAcceptsCustomSort(): void
    {
        $sort = new \Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria(
            field:     \Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField::LastName,
            direction: \Mossetc\TechnicalTest\Shared\Domain\SortDirection::Desc,
        );
        $cmd = new ListUsers(sort: $sort);

        $this->assertSame($sort, $cmd->sort);
    }
}
