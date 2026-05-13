<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Integration\Auth;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class PdoUserRepositoryTest extends TestCase
{
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
    }

    private function makeUser(string $email = 'user@example.com', string $password = 'password123'): User
    {
        return new User(
            id:        UserId::generate(),
            email:     new Email($email),
            password:  HashedPassword::fromPlain(new PlainPassword($password)),
            firstName: new FirstName('Test'),
            lastName:  new LastName('User'),
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

    public function testPreservesAllFields(): void
    {
        $user = new User(
            id:          UserId::generate(),
            email:       new Email('alice@example.com'),
            password:    HashedPassword::fromPlain(new PlainPassword('secret123')),
            firstName:   new FirstName('Alice'),
            lastName:    new LastName('Smith'),
            role:        Role::CompanyAdmin,
            companyId:   '11111111-1111-4111-8111-111111111111',
            phoneNumber: '+33612345678',
        );
        $this->repository->save($user);

        $found = $this->repository->findById($user->id);

        $this->assertNotNull($found);
        $this->assertSame('Alice', $found->firstName->value);
        $this->assertSame('Smith', $found->lastName->value);
        $this->assertSame(Role::CompanyAdmin, $found->role);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $found->companyId);
        $this->assertSame('+33612345678', $found->phoneNumber);
    }

    public function testTimestampsArePresentAfterSaveAndFind(): void
    {
        $before = new DateTimeImmutable();
        $user   = $this->makeUser();
        $this->repository->save($user);
        $after  = new DateTimeImmutable();

        $found = $this->repository->findById($user->id);

        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual($before, $found->createdAt);
        $this->assertLessThanOrEqual($after, $found->createdAt);
        $this->assertNull($found->deletedAt);
    }

    public function testDeleteSoftRemovesUser(): void
    {
        $user = $this->makeUser();
        $this->repository->save($user);
        $this->repository->delete($user->id);

        $this->assertNull($this->repository->findById($user->id));
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

    public function testFindPaginatedByCompanyIds(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';

        $this->repository->save(new User(
            id: UserId::generate(), email: new Email('cm@example.com'),
            password: HashedPassword::fromPlain(new PlainPassword('pass12345')),
            firstName: new FirstName('A'), lastName: new LastName('B'),
            role: Role::CompanyAdmin, companyId: $company,
        ));
        $this->repository->save($this->makeUser('other@example.com'));

        $found = $this->repository->findPaginatedByCompanyIds([$company], 10, 0);

        $this->assertCount(1, $found);
        $this->assertSame('cm@example.com', $found[0]->email->value);
    }
}
