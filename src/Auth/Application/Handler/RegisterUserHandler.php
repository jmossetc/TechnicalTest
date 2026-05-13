<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserRole;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRoleRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\PasswordHasherInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private UserRoleRepositoryInterface $roleRepository,
        private PasswordHasherInterface $passwordHasher,
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
            password: $this->passwordHasher->hash(new PlainPassword($command->password)),
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
