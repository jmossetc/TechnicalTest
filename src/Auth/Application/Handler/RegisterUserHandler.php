<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Query\RegisterUser;
use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;

final readonly class RegisterUserHandler
{
    public function __construct(private UserRepositoryInterface $repository) {}

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

        return $userId;
    }
}
