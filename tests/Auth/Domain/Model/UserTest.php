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
            id:        UserId::generate(),
            email:     new Email($email),
            password:  HashedPassword::fromPlain(new PlainPassword($password)),
            firstName: new FirstName('Alice'),
            lastName:  new LastName('Smith'),
        );
    }

    public function testHoldsExpectedProperties(): void
    {
        $id    = UserId::generate();
        $email = new Email('alice@example.com');

        $user = new User(
            id:        $id,
            email:     $email,
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Alice'),
            lastName:  new LastName('Smith'),
        );

        $this->assertTrue($user->id->equals($id));
        $this->assertTrue($user->email->equals($email));
        $this->assertSame('Alice', $user->firstName->value);
        $this->assertSame('Smith', $user->lastName->value);
        $this->assertSame(Role::Employee, $user->role);
    }

    public function testVerifiesCorrectPassword(): void
    {
        $user = $this->makeUser(password: 'correctpass');

        $this->assertTrue($user->verifyPassword(new PlainPassword('correctpass')));
    }

    public function testRejectsWrongPassword(): void
    {
        $user = $this->makeUser(password: 'correctpass');

        $this->assertFalse($user->verifyPassword(new PlainPassword('wrongpasss')));
    }

    public function testTimestampsDefaultToCurrentTime(): void
    {
        $before = new DateTimeImmutable();
        $user   = $this->makeUser();
        $after  = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $user->createdAt);
        $this->assertLessThanOrEqual($after, $user->createdAt);
    }

    public function testDeletedAtIsNullByDefault(): void
    {
        $this->assertNull($this->makeUser()->deletedAt);
    }

    public function testIsActiveByDefault(): void
    {
        $this->assertTrue($this->makeUser()->isActive);
    }

    public function testRoleDefaultsToEmployee(): void
    {
        $this->assertSame(Role::Employee, $this->makeUser()->role);
    }

    public function testAcceptsExplicitRole(): void
    {
        $user = new User(
            id:        UserId::generate(),
            email:     new Email('admin@example.com'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Admin'),
            lastName:  new LastName('User'),
            role:      Role::Admin,
        );

        $this->assertSame(Role::Admin, $user->role);
    }
}
