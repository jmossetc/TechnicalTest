<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\UpdateUser;
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
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepositoryInterface  $repository,
        private UserAuthorizationService $authorizationService,
    ) {}

    public function handle(UpdateUser $command): void
    {
        try {
            $targetId = new UserId($command->targetId);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException('Invalid user ID');
        }

        $target = $this->repository->findById($targetId);
        if ($target === null) {
            throw new UserNotFoundException();
        }

        $permissions = $this->authorizationService->authorizeUserUpdate($command->callerId, $target);
        $input       = $command->input;
        $isSelf      = $command->callerId->equals($targetId);

        $firstName   = $target->firstName;
        $lastName    = $target->lastName;
        $email       = $target->email;
        $phoneNumber = $target->phoneNumber;
        $password    = $target->password;
        $isActive    = $target->isActive;
        $role        = $target->role;
        $companyId   = $target->companyId;
        $shopId      = $target->shopId;

        if ($permissions->canEditProfile) {
            if ($input->firstName !== null) {
                $firstName = new FirstName($input->firstName);
            }

            if ($input->lastName !== null) {
                $lastName = new LastName($input->lastName);
            }

            if ($input->email !== null) {
                $newEmail = new Email($input->email);
                if (!$newEmail->equals($target->email)) {
                    if ($this->repository->findByEmail($newEmail) !== null) {
                        throw new UserAlreadyExistsException($newEmail);
                    }
                }
                $email = $newEmail;
            }

            if ($input->phoneNumber !== null) {
                $phoneNumber = $input->phoneNumber;
            }
        }

        // Password: self-only or admin (canEditRole). Silently ignored for all others.
        if ($input->password !== null && ($isSelf || $permissions->canEditRole)) {
            if ($isSelf) {
                if ($input->currentPassword === null) {
                    throw new InvalidArgumentException('current_password is required to change password');
                }
                if (!$target->password->verify(new PlainPassword($input->currentPassword))) {
                    throw new InvalidArgumentException('current_password is incorrect');
                }
            }
            $password = HashedPassword::fromPlain(new PlainPassword($input->password));
        }

        if ($permissions->canEditStatus && $input->isActive !== null) {
            $isActive = $input->isActive;
        }

        if ($permissions->canEditRole) {
            if ($input->role !== null) {
                $role = Role::from($input->role);
            }
            if ($input->companyId !== null) {
                $companyId = $input->companyId;
            }
            if ($input->shopId !== null) {
                $shopId = $input->shopId;
            }
        }

        $this->repository->save(new User(
            id:          $target->id,
            email:       $email,
            password:    $password,
            firstName:   $firstName,
            lastName:    $lastName,
            role:        $role,
            companyId:   $companyId,
            shopId:      $shopId,
            phoneNumber: $phoneNumber,
            isActive:    $isActive,
            lastLoginAt: $target->lastLoginAt,
            createdAt:   $target->createdAt,
            deletedAt:   $target->deletedAt,
        ));
    }
}
