<?php

declare(strict_types=1);

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $store = [];

    public function save(User $user): void
    {
        $this->store[$user->id->value] = $user;
    }

    public function findByEmail(Email $email): ?User
    {
        foreach ($this->store as $user) {
            if ($user->email->equals($email)) {
                return $user;
            }
        }

        return null;
    }

    public function findById(UserId $id): ?User
    {
        return $this->store[$id->value] ?? null;
    }

    public function delete(UserId $id): void
    {
        unset($this->store[$id->value]);
    }

    public function updateLastLogin(UserId $id): void
    {
        if (!isset($this->store[$id->value])) {
            return;
        }
        $u = $this->store[$id->value];
        $this->store[$id->value] = new User(
            id: $u->id,
            email: $u->email,
            password: $u->password,
            firstName: $u->firstName,
            lastName: $u->lastName,
            role: $u->role,
            companyId: $u->companyId,
            shopId: $u->shopId,
            phoneNumber: $u->phoneNumber,
            isActive: $u->isActive,
            lastLoginAt: new DateTimeImmutable(),
            createdAt: $u->createdAt,
            updatedAt: $u->updatedAt,
            deletedAt: $u->deletedAt,
        );
    }

    public function findPaginatedByCriteria(
        UserSearchCriteria $criteria,
        UserScope          $scope,
        UserSortCriteria   $sort,
        int                $limit,
        int                $offset,
    ): array {
        $users = array_values(array_filter(
            $this->store,
            fn(User $u): bool => $this->matchesScope($u, $scope) && $this->matchesCriteria($u, $criteria),
        ));
        usort($users, $this->buildComparator($sort));

        return array_slice($users, $offset, $limit);
    }

    public function countByCriteria(UserSearchCriteria $criteria, UserScope $scope): int
    {
        return count(array_filter(
            $this->store,
            fn(User $u): bool => $this->matchesScope($u, $scope) && $this->matchesCriteria($u, $criteria),
        ));
    }

    /** @return callable(User, User): int */
    private function buildComparator(UserSortCriteria $sort): callable
    {
        return static function (User $a, User $b) use ($sort): int {
            $cmp = match ($sort->field) {
                UserSortField::Email     => strcmp($a->email->value, $b->email->value),
                UserSortField::FirstName => strcmp($a->firstName->value, $b->firstName->value),
                UserSortField::LastName  => strcmp($a->lastName->value, $b->lastName->value),
                UserSortField::Role      => strcmp($a->role->value, $b->role->value),
                UserSortField::CreatedAt => $a->createdAt <=> $b->createdAt,
                UserSortField::UpdatedAt => $a->updatedAt <=> $b->updatedAt,
            };

            return $sort->direction === SortDirection::Desc ? -$cmp : $cmp;
        };
    }

    private function matchesScope(User $u, UserScope $scope): bool
    {
        if ($scope->isAll()) {
            return true;
        }

        if ($scope->isCompanies()) {
            return $u->companyId !== null && in_array($u->companyId, $scope->ids, true);
        }

        if ($scope->isShops()) {
            return $u->shopId !== null
                && in_array($u->shopId, $scope->ids, true)
                && ($scope->scopeCompanyId === null || $u->companyId === $scope->scopeCompanyId);
        }

        return false;
    }

    private function matchesCriteria(User $u, UserSearchCriteria $criteria): bool
    {
        if ($criteria->email !== null
            && !str_contains(strtolower($u->email->value), strtolower($criteria->email))) {
            return false;
        }

        if ($criteria->firstName !== null
            && !str_contains(strtolower($u->firstName->value), strtolower($criteria->firstName))) {
            return false;
        }

        if ($criteria->lastName !== null
            && !str_contains(strtolower($u->lastName->value), strtolower($criteria->lastName))) {
            return false;
        }

        if ($criteria->phoneNumber !== null
            && ($u->phoneNumber === null
                || !str_contains(strtolower($u->phoneNumber), strtolower($criteria->phoneNumber)))) {
            return false;
        }

        if ($criteria->role !== null && $u->role !== $criteria->role) {
            return false;
        }

        if ($criteria->isActive !== null && $u->isActive !== $criteria->isActive) {
            return false;
        }

        if ($criteria->createdFrom !== null && $u->createdAt < $criteria->createdFrom) {
            return false;
        }

        if ($criteria->createdTo !== null && $u->createdAt > $criteria->createdTo) {
            return false;
        }

        if ($criteria->lastLoginFrom !== null
            && ($u->lastLoginAt === null || $u->lastLoginAt < $criteria->lastLoginFrom)) {
            return false;
        }

        if ($criteria->lastLoginTo !== null
            && ($u->lastLoginAt === null || $u->lastLoginAt > $criteria->lastLoginTo)) {
            return false;
        }

        return true;
    }
}
