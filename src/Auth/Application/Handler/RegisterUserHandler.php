<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\PasswordHasherInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function handle(RegisterUser $command): UserId
    {
        $email = new Email($command->email);

        if ($this->repository->findByEmail($email) !== null) {
            throw new UserAlreadyExistsException($email);
        }

        $userId = UserId::generate();

        $this->repository->save(new User(
            id:          $userId,
            email:       $email,
            password:    $this->passwordHasher->hash(new PlainPassword($command->password)),
            firstName:   new FirstName($command->firstName),
            lastName:    new LastName($command->lastName),
            role:        Role::from($command->role),
            companyId:   $command->companyId,
            shopId:      $command->shopId,
            phoneNumber: $command->phoneNumber,
        ));

        return $userId;
    }
}
