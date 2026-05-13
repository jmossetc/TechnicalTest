<?php

namespace Mossetc\TechnicalTest\Auth\Domain\Service;

use Mossetc\TechnicalTest\Auth\Domain\Exception\UserNotFoundException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;

final readonly class UserDeletion
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserAuthorization $authorizationService,
    ) {
    }

    public function deleteUser(UserId $targetId, UserId $callerId): void
    {
        $target = $this->userRepository->findById($targetId);
        if ($target === null) {
            throw new UserNotFoundException();
        }

        $this->authorizationService->authorizeDeletion($callerId, $target);
    }
}