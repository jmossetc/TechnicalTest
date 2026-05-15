<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain\Model;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function makeUser(string $email = 'user@example.com', string $password = 'password123'): User
    {
        return new User(
            id: UserId::generate(),
            email: new Email($email),
            password: HashedPassword::fromPlain(new PlainPassword($password)),
            firstName: new FirstName('Alice'),
            lastName: new LastName('Smith'),
        );
    }

    public function testHoldsExpectedProperties(): void
    {
        $id    = UserId::generate();
        $email = new Email('alice@example.com');

        $user = new User(
            id: $id,
            email: $email,
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Alice'),
            lastName: new LastName('Smith'),
        );

        self::assertTrue($user->id->equals($id));
        self::assertTrue($user->email->equals($email));
        self::assertSame('Alice', $user->firstName->value);
        self::assertSame('Smith', $user->lastName->value);
        self::assertSame(Role::Employee, $user->role);
    }

    public function testVerifiesCorrectPassword(): void
    {
        $user = $this->makeUser(password: 'correctpass');

        self::assertTrue($user->verifyPassword(new PlainPassword('correctpass')));
    }

    public function testRejectsWrongPassword(): void
    {
        $user = $this->makeUser(password: 'correctpass');

        self::assertFalse($user->verifyPassword(new PlainPassword('wrongpasss')));
    }

    public function testTimestampsDefaultToCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $user   = $this->makeUser();
        $after  = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before, $user->createdAt);
        self::assertLessThanOrEqual($after, $user->createdAt);
    }

    public function testDeletedAtIsNullByDefault(): void
    {
        self::assertNull($this->makeUser()->deletedAt);
    }

    public function testIsActiveByDefault(): void
    {
        self::assertTrue($this->makeUser()->isActive);
    }

    public function testRoleDefaultsToEmployee(): void
    {
        self::assertSame(Role::Employee, $this->makeUser()->role);
    }

    public function testAcceptsExplicitRole(): void
    {
        $user = new User(
            id: UserId::generate(),
            email: new Email('admin@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Admin'),
            lastName: new LastName('User'),
            role: Role::Admin,
        );

        self::assertSame(Role::Admin, $user->role);
    }
}
