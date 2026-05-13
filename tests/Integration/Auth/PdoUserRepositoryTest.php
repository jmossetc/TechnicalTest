<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Infrastructure\Repository\PdoUserRepository;
use Mossetc\TechnicalTest\Tests\Integration\Support\DatabaseTestCase;

final class PdoUserRepositoryTest extends DatabaseTestCase
{
    private PdoUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PdoUserRepository($this->pdo);
    }

    private function makeUser(
        string $email = 'user@example.com',
        string $password = 'password123',
    ): User {
        return new User(
            id: UserId::generate(),
            email: new Email($email),
            password: HashedPassword::fromPlain(new PlainPassword($password)),
        );
    }

    public function testSavesAndFindsById(): void
    {
        $user = $this->makeUser();
        $this->repository->save($user);

        $found = $this->repository->findById($user->id);

        $this->assertNotNull($found);
        $this->assertTrue($user->id->equals($found->id));
        $this->assertTrue($user->email->equals($found->email));
    }

    public function testSavesAndFindsByEmail(): void
    {
        $user = $this->makeUser('alice@example.com');
        $this->repository->save($user);

        $found = $this->repository->findByEmail(new Email('alice@example.com'));

        $this->assertNotNull($found);
        $this->assertSame('alice@example.com', $found->email->value);
    }

    public function testReturnsNullWhenNotFoundById(): void
    {
        $this->assertNull($this->repository->findById(UserId::generate()));
    }

    public function testReturnsNullWhenNotFoundByEmail(): void
    {
        $this->assertNull($this->repository->findByEmail(new Email('ghost@example.com')));
    }

    public function testPreservesHashedPassword(): void
    {
        $plain = new PlainPassword('secret123');
        $user  = $this->makeUser(password: 'secret123');
        $this->repository->save($user);

        $found = $this->repository->findById($user->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->verifyPassword($plain));
    }

    public function testSaveUpdatesExistingUser(): void
    {
        $user = $this->makeUser('alice@example.com', 'password123');
        $this->repository->save($user);

        $updated = new User(
            id: $user->id,
            email: new Email('alice@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('newpassword')),
        );
        $this->repository->save($updated);

        $found = $this->repository->findById($user->id);

        $this->assertNotNull($found);
        $this->assertTrue($found->verifyPassword(new PlainPassword('newpassword')));
        $this->assertFalse($found->verifyPassword(new PlainPassword('password123')));
    }

    public function testFindsCorrectUserAmongMultiple(): void
    {
        $alice = $this->makeUser('alice@example.com');
        $bob   = $this->makeUser('bob@example.com');
        $this->repository->save($alice);
        $this->repository->save($bob);

        $found = $this->repository->findByEmail(new Email('bob@example.com'));

        $this->assertNotNull($found);
        $this->assertTrue($bob->id->equals($found->id));
    }
}
