<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Domain;

use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserNotFoundException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserDeletion;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class UserDeletionTest extends TestCase
{
    private InMemoryUserRepository $repository;
    private UserId $callerId;
    private UserId $targetId;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUserRepository();
        $this->callerId   = UserId::generate();
        $this->targetId   = UserId::generate();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function service(): UserDeletion
    {
        return new UserDeletion($this->repository, new UserAuthorization($this->repository));
    }

    private function seed(UserId $id, Role $role, ?string $companyId = null, ?string $shopId = null): void
    {
        $this->repository->save(new User(
            id:        $id,
            email:     new Email($id->value . '@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Test'),
            lastName:  new LastName('User'),
            role:      $role,
            companyId: $companyId,
            shopId:    $shopId,
        ));
    }

    // ── UserNotFoundException ─────────────────────────────────────────────────

    public function testThrowsWhenTargetDoesNotExist(): void
    {
        $this->seed($this->callerId, Role::Admin);

        $this->expectException(UserNotFoundException::class);
        $this->service()->deleteUser($this->targetId, $this->callerId);
    }

    public function testExceptionMessageSaysUserNotFound(): void
    {
        $this->seed($this->callerId, Role::Admin);

        try {
            $this->service()->deleteUser($this->targetId, $this->callerId);
            $this->fail('Expected UserNotFoundException');
        } catch (UserNotFoundException $e) {
            $this->assertStringContainsStringIgnoringCase('not found', $e->getMessage());
        }
    }

    // ── Successful deletion ───────────────────────────────────────────────────

    public function testAdminCanDeleteEmployee(): void
    {
        $this->seed($this->callerId, Role::Admin);
        $this->seed($this->targetId, Role::Employee);

        $this->service()->deleteUser($this->targetId, $this->callerId);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanDeleteShopManager(): void
    {
        $this->seed($this->callerId, Role::Admin);
        $this->seed($this->targetId, Role::ShopManager, shopId: 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

        $this->service()->deleteUser($this->targetId, $this->callerId);

        $this->addToAssertionCount(1);
    }

    public function testAdminCanDeleteCompanyAdmin(): void
    {
        $this->seed($this->callerId, Role::Admin);
        $this->seed($this->targetId, Role::CompanyAdmin, companyId: '11111111-1111-4111-8111-111111111111');

        $this->service()->deleteUser($this->targetId, $this->callerId);

        $this->addToAssertionCount(1);
    }

    public function testCompanyAdminCanDeleteEmployeeInOwnCompany(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';
        $this->seed($this->callerId, Role::CompanyAdmin, companyId: $company);
        $this->seed($this->targetId, Role::Employee,     companyId: $company);

        $this->service()->deleteUser($this->targetId, $this->callerId);

        $this->addToAssertionCount(1);
    }

    public function testCompanyAdminCanDeleteShopManagerInOwnCompany(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';
        $this->seed($this->callerId, Role::CompanyAdmin, companyId: $company);
        $this->seed($this->targetId, Role::ShopManager,  companyId: $company, shopId: 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa');

        $this->service()->deleteUser($this->targetId, $this->callerId);

        $this->addToAssertionCount(1);
    }

    // ── ForbiddenException ────────────────────────────────────────────────────

    public function testNobodyCanDeleteAdmin(): void
    {
        $this->seed($this->callerId, Role::Admin);
        $this->seed($this->targetId, Role::Admin);

        $this->expectException(ForbiddenException::class);
        $this->service()->deleteUser($this->targetId, $this->callerId);
    }

    public function testCompanyAdminCannotDeleteAnotherCompanyAdmin(): void
    {
        $company = '11111111-1111-4111-8111-111111111111';
        $this->seed($this->callerId, Role::CompanyAdmin, companyId: $company);
        $this->seed($this->targetId, Role::CompanyAdmin, companyId: $company);

        $this->expectException(ForbiddenException::class);
        $this->service()->deleteUser($this->targetId, $this->callerId);
    }

    public function testCompanyAdminCannotDeleteUserInAnotherCompany(): void
    {
        $this->seed($this->callerId, Role::CompanyAdmin, companyId: '11111111-1111-4111-8111-111111111111');
        $this->seed($this->targetId, Role::Employee,     companyId: '22222222-2222-4222-8222-222222222222');

        $this->expectException(ForbiddenException::class);
        $this->service()->deleteUser($this->targetId, $this->callerId);
    }

    public function testShopManagerCannotDeleteAnyone(): void
    {
        $this->seed($this->callerId, Role::ShopManager, shopId: 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $this->seed($this->targetId, Role::Employee);

        $this->expectException(ForbiddenException::class);
        $this->service()->deleteUser($this->targetId, $this->callerId);
    }

    public function testEmployeeCannotDeleteAnyone(): void
    {
        $this->seed($this->callerId, Role::Employee);
        $this->seed($this->targetId, Role::Employee);

        $this->expectException(ForbiddenException::class);
        $this->service()->deleteUser($this->targetId, $this->callerId);
    }
}
