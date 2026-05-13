<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Unit\Auth\Domain;

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
}
