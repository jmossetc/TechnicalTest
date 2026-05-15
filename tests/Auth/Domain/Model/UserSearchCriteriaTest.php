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

        self::assertNull($c->email);
        self::assertNull($c->firstName);
        self::assertNull($c->lastName);
        self::assertNull($c->phoneNumber);
        self::assertNull($c->role);
        self::assertNull($c->isActive);
        self::assertNull($c->createdFrom);
        self::assertNull($c->createdTo);
        self::assertNull($c->lastLoginFrom);
        self::assertNull($c->lastLoginTo);
    }

    public function testAcceptsAllFields(): void
    {
        $from = new DateTimeImmutable('2025-01-01');
        $to   = new DateTimeImmutable('2025-12-31');

        $c = new UserSearchCriteria(
            email: 'ali',
            firstName: 'Al',
            lastName: 'ice',
            phoneNumber: '06',
            role: Role::Employee,
            isActive: true,
            createdFrom: $from,
            createdTo: $to,
            lastLoginFrom: $from,
            lastLoginTo: $to,
        );

        self::assertSame('ali', $c->email);
        self::assertSame(Role::Employee, $c->role);
        self::assertTrue($c->isActive);
        self::assertSame($from, $c->createdFrom);
    }

    public function testRejectsCreatedFromAfterCreatedTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_from must not be after created_to');

        new UserSearchCriteria(
            createdFrom: new DateTimeImmutable('2025-02-01'),
            createdTo: new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testRejectsLastLoginFromAfterLastLoginTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('last_login_from must not be after last_login_to');

        new UserSearchCriteria(
            lastLoginFrom: new DateTimeImmutable('2025-02-01'),
            lastLoginTo: new DateTimeImmutable('2025-01-01'),
        );
    }

    public function testSameDateBoundariesAreValid(): void
    {
        $date = new DateTimeImmutable('2025-06-15');

        $c = new UserSearchCriteria(createdFrom: $date, createdTo: $date);

        self::assertSame($date, $c->createdFrom);
        self::assertSame($date, $c->createdTo);
    }
}
