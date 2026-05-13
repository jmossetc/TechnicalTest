<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Query\LoginUser;
use Mossetc\TechnicalTest\Auth\Domain\AuthToken;
use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Domain\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;

final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private TokenServiceInterface $tokenService,
    ) {}

    public function handle(LoginUser $command): AuthToken
    {
        $email = new Email($command->email);
        $user = $this->repository->findByEmail($email);

        if ($user === null || !$user->verifyPassword(new PlainPassword($command->password))) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenService->issue($user->id, $user->email);
    }
}
