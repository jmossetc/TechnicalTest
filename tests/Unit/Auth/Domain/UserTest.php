<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Domain;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private function makeUser(string $email = 'user@example.com', string $password = 'password123'): User
    {
        return new User(
            id: UserId::generate(),
            email: new Email($email),
            password: HashedPassword::fromPlain(new PlainPassword($password)),
        );
    }

    public function testHoldsExpectedProperties(): void
    {
        $id = UserId::generate();
        $email = new Email('alice@example.com');
        $password = HashedPassword::fromPlain(new PlainPassword('password123'));

        $user = new User(id: $id, email: $email, password: $password);

        $this->assertTrue($user->id->equals($id));
        $this->assertTrue($user->email->equals($email));
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
        $this->assertGreaterThanOrEqual($before, $user->updatedAt);
        $this->assertLessThanOrEqual($after, $user->updatedAt);
    }

    public function testDeletedAtIsNullByDefault(): void
    {
        $this->assertNull($this->makeUser()->deletedAt);
    }

    public function testAcceptsExplicitTimestamps(): void
    {
        $created = new DateTimeImmutable('2024-01-01 10:00:00');
        $updated = new DateTimeImmutable('2024-06-15 12:30:00');
        $deleted = new DateTimeImmutable('2024-12-01 09:00:00');

        $user = new User(
            id: UserId::generate(),
            email: new Email('alice@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('password123')),
            createdAt: $created,
            updatedAt: $updated,
            deletedAt: $deleted,
        );

        $this->assertSame($created, $user->createdAt);
        $this->assertSame($updated, $user->updatedAt);
        $this->assertSame($deleted, $user->deletedAt);
    }
}
