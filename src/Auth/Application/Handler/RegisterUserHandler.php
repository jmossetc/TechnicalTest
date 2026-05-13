<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Query\RegisterUser;
use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Role;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\UserRole;
use Mossetc\TechnicalTest\Auth\Domain\UserRoleRepositoryInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private UserRoleRepositoryInterface $roleRepository,
    ) {}

    public function handle(RegisterUser $command): UserId
    {
        $email = new Email($command->email);

        if ($this->repository->findByEmail($email) !== null) {
            throw new UserAlreadyExistsException($email);
        }

        $userId = UserId::generate();
        $user = new User(
            id: $userId,
            email: $email,
            password: HashedPassword::fromPlain(new PlainPassword($command->password)),
        );

        $this->repository->save($user);

        if ($command->role !== null) {
            $role = Role::from($command->role);
            $userRole = match ($role) {
                Role::Admin => new UserRole(Role::Admin),
                Role::CompanyManager => new UserRole(Role::CompanyManager, companyId: $command->companyId),
                Role::ShopManager => new UserRole(Role::ShopManager, shopId: $command->shopId),
            };
            $this->roleRepository->grantRole($userId, $userRole);
        }

        return $userId;
    }
}
