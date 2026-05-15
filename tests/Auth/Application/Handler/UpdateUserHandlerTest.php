<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Auth\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\UpdateUser;
use Mossetc\TechnicalTest\Auth\Application\DTO\UserUpdateInput;
use Mossetc\TechnicalTest\Auth\Application\Handler\UpdateUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserNotFoundException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;

final class UpdateUserHandlerTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string SHOP_A    = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private InMemoryUserRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new InMemoryUserRepository();
    }

    private function handler(): UpdateUserHandler
    {
        return new UpdateUserHandler($this->repo, new UserAuthorizationService($this->repo));
    }

    private function seedUser(
        UserId $id,
        Role $role,
        ?string $companyId = null,
        ?string $shopId = null,
        string $password = 'password123',
        string $email = '',
    ): User {
        $email = $email !== '' ? $email : $id->value . '@test.test';
        $user  = new User(
            id: $id,
            email: new Email($email),
            password: HashedPassword::fromPlain(new PlainPassword($password)),
            firstName: new FirstName('Test'),
            lastName: new LastName('User'),
            role: $role,
            companyId: $companyId,
            shopId: $shopId,
        );
        $this->repo->save($user);

        return $user;
    }

    public function testSelfCanUpdateFirstAndLastName(): void
    {
        $userId = UserId::generate();
        $this->seedUser($userId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(firstName: 'Jane', lastName: 'Smith');
        $this->handler()->handle(new UpdateUser($userId, $userId->value, $input));

        $updated = $this->repo->findById($userId);
        self::assertNotNull($updated);
        self::assertSame('Jane', $updated->firstName->value);
        self::assertSame('Smith', $updated->lastName->value);
    }

    public function testSelfCanChangePasswordWithCorrectCurrentPassword(): void
    {
        $userId = UserId::generate();
        $this->seedUser($userId, Role::Employee, self::COMPANY_A, password: 'oldPassword1');

        $input = new UserUpdateInput(password: 'newPassword1', currentPassword: 'oldPassword1');
        $this->handler()->handle(new UpdateUser($userId, $userId->value, $input));

        $updated = $this->repo->findById($userId);
        self::assertNotNull($updated);
        self::assertTrue($updated->password->verify(new PlainPassword('newPassword1')));
    }

    public function testSelfThrowsWhenCurrentPasswordMissing(): void
    {
        $userId = UserId::generate();
        $this->seedUser($userId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(password: 'newPassword1');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('current_password is required');
        $this->handler()->handle(new UpdateUser($userId, $userId->value, $input));
    }

    public function testSelfThrowsWhenCurrentPasswordWrong(): void
    {
        $userId = UserId::generate();
        $this->seedUser($userId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(password: 'newPassword1', currentPassword: 'wrongPassword123');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('current_password is incorrect');
        $this->handler()->handle(new UpdateUser($userId, $userId->value, $input));
    }

    public function testSelfCannotChangeIsActive(): void
    {
        $userId = UserId::generate();
        $this->seedUser($userId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(isActive: false);
        $this->handler()->handle(new UpdateUser($userId, $userId->value, $input));

        $updated = $this->repo->findById($userId);
        self::assertNotNull($updated);
        self::assertTrue($updated->isActive); // silently ignored
    }

    public function testAdminCanChangeRole(): void
    {
        $adminId  = UserId::generate();
        $targetId = UserId::generate();
        $this->seedUser($adminId, Role::Admin);
        $this->seedUser($targetId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(role: 'company_admin', companyId: self::COMPANY_A);
        $this->handler()->handle(new UpdateUser($adminId, $targetId->value, $input));

        $updated = $this->repo->findById($targetId);
        self::assertNotNull($updated);
        self::assertSame(Role::CompanyAdmin, $updated->role);
        self::assertSame(self::COMPANY_A, $updated->companyId);
    }

    public function testAdminCanChangePasswordWithoutCurrentPassword(): void
    {
        $adminId  = UserId::generate();
        $targetId = UserId::generate();
        $this->seedUser($adminId, Role::Admin);
        $this->seedUser($targetId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(password: 'newPassword1');
        $this->handler()->handle(new UpdateUser($adminId, $targetId->value, $input));

        $updated = $this->repo->findById($targetId);
        self::assertNotNull($updated);
        self::assertTrue($updated->password->verify(new PlainPassword('newPassword1')));
    }

    public function testEmailConflictThrows(): void
    {
        $userId1 = UserId::generate();
        $userId2 = UserId::generate();
        $this->seedUser($userId1, Role::Employee, self::COMPANY_A);
        $this->seedUser($userId2, Role::Employee, self::COMPANY_A, email: 'taken@example.com');

        $input = new UserUpdateInput(email: 'taken@example.com');

        $this->expectException(UserAlreadyExistsException::class);
        $this->handler()->handle(new UpdateUser($userId1, $userId1->value, $input));
    }

    public function testSameEmailDoesNotConflict(): void
    {
        $userId = UserId::generate();
        $user   = $this->seedUser($userId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(email: $user->email->value);
        $this->handler()->handle(new UpdateUser($userId, $userId->value, $input)); // no exception

        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenTargetNotFound(): void
    {
        $callerId = UserId::generate();
        $this->seedUser($callerId, Role::Admin);

        $input = new UserUpdateInput(firstName: 'Jane');

        $this->expectException(UserNotFoundException::class);
        $this->handler()->handle(new UpdateUser($callerId, UserId::generate()->value, $input));
    }

    public function testShopManagerCannotChangeEmployeePassword(): void
    {
        $managerId = UserId::generate();
        $empId     = UserId::generate();
        $this->seedUser($managerId, Role::ShopManager, self::COMPANY_A, self::SHOP_A);
        $this->seedUser($empId, Role::Employee, self::COMPANY_A, self::SHOP_A, password: 'oldPassword1');

        $input = new UserUpdateInput(password: 'newPassword1');
        $this->handler()->handle(new UpdateUser($managerId, $empId->value, $input));

        $updated = $this->repo->findById($empId);
        self::assertNotNull($updated);
        self::assertTrue($updated->password->verify(new PlainPassword('oldPassword1'))); // unchanged
    }

    public function testCompanyAdminCanSetIsActive(): void
    {
        $adminId  = UserId::generate();
        $targetId = UserId::generate();
        $this->seedUser($adminId, Role::CompanyAdmin, self::COMPANY_A);
        $this->seedUser($targetId, Role::Employee, self::COMPANY_A);

        $input = new UserUpdateInput(isActive: false);
        $this->handler()->handle(new UpdateUser($adminId, $targetId->value, $input));

        $updated = $this->repo->findById($targetId);
        self::assertNotNull($updated);
        self::assertFalse($updated->isActive);
    }
}
