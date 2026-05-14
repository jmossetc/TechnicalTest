<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Repository;

use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findByEmail(Email $email): ?User;

    public function findById(UserId $id): ?User;

    public function delete(UserId $id): void;

    public function updateLastLogin(UserId $id): void;

    // ── Listing ───────────────────────────────────────────────────────────────

    /** @return list<User> */
    public function findPaginatedByCriteria(
        UserSearchCriteria $criteria,
        UserScope $scope,
        int $limit,
        int $offset,
    ): array;

    public function countByCriteria(UserSearchCriteria $criteria, UserScope $scope): int;
}
