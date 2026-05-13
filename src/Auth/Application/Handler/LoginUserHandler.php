<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\LoginUser;
use Mossetc\TechnicalTest\Auth\Domain\Model\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\PasswordHasherInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;

final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private TokenServiceInterface $tokenService,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function handle(LoginUser $command): AuthToken
    {
        $email = new Email($command->email);
        $user = $this->repository->findByEmail($email);

        $plain = new PlainPassword($command->password);
        if ($user === null || !$this->passwordHasher->verify($plain, $user->password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenService->issue($user->id, $user->email);
    }
}
