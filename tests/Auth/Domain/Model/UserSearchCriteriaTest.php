<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use PHPUnit\Framework\TestCase;

final class UserSearchCriteriaTest extends TestCase
{
    public function testAllFieldsNullByDefault(): void
    {
        $c = new UserSearchCriteria();

        $this->assertNull($c->email);
        $this->assertNull($c->firstName);
        $this->assertNull($c->lastName);
        $this->assertNull($c->phoneNumber);
        $this->assertNull($c->role);
        $this->assertNull($c->isActive);
        $this->assertNull($c->createdFrom);
        $this->assertNull($c->createdTo);
        $this->assertNull($c->lastLoginFrom);
        $this->assertNull($c->lastLoginTo);
    }

    public function testAcceptsAllFields(): void
    {
        $from = new DateTimeImmutable('2025-01-01');
        $to   = new DateTimeImmutable('2025-12-31');

        $c = new UserSearchCriteria(
            email:         'ali',
            firstName:     'Al',
            lastName:      'ice',
            phoneNumber:   '06',
            role:          Role::Employee,
            isActive:      true,
            createdFrom:   $from,
            createdTo:     $to,
            lastLoginFrom: $from,
            lastLoginTo:   $to,
        );

        $this->assertSame('ali', $c->email);
        $this->assertSame(Role::Employee, $c->role);
        $this->assertTrue($c->isActive);
        $this->assertSame($from, $c->createdFrom);
    }

    public function testRejectsCreatedFromAfterCreatedTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_from must not be after created_to');

        new UserSearchCriteria(
            createdFrom: new DateTimeImmutable('2025-02-01'),
            createdTo:   new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testRejectsLastLoginFromAfterLastLoginTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('last_login_from must not be after last_login_to');

        new UserSearchCriteria(
            lastLoginFrom: new DateTimeImmutable('2025-02-01'),
            lastLoginTo:   new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testSameDateBoundariesAreValid(): void
    {
        $date = new DateTimeImmutable('2025-06-15');

        $c = new UserSearchCriteria(createdFrom: $date, createdTo: $date);

        $this->assertSame($date, $c->createdFrom);
        $this->assertSame($date, $c->createdTo);
    }
}
