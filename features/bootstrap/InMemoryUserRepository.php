<?php

declare(strict_types=1);

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;

/**
 * In-memory implementation of UserRepositoryInterface for Behat scenarios.
 */
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
            id:          $u->id,
            email:       $u->email,
            password:    $u->password,
            firstName:   $u->firstName,
            lastName:    $u->lastName,
            role:        $u->role,
            companyId:   $u->companyId,
            shopId:      $u->shopId,
            phoneNumber: $u->phoneNumber,
            isActive:    $u->isActive,
            lastLoginAt: new DateTimeImmutable(),
            createdAt:   $u->createdAt,
            updatedAt:   $u->updatedAt,
            deletedAt:   $u->deletedAt,
        );
    }

    public function findPaginated(int $limit, int $offset): array
    {
        $users = array_values($this->store);
        usort($users, static fn(User $a, User $b): int => strcmp($a->email->value, $b->email->value));

        return array_slice($users, $offset, $limit);
    }

    public function count(): int
    {
        return count($this->store);
    }

    public function findPaginatedByCompanyIds(array $companyIds, int $limit, int $offset): array
    {
        if ($companyIds === []) {
            return [];
        }

        $users = array_values(array_filter(
            $this->store,
            static fn(User $u): bool => $u->companyId !== null && in_array($u->companyId, $companyIds, true),
        ));
        usort($users, static fn(User $a, User $b): int => strcmp($a->email->value, $b->email->value));

        return array_slice($users, $offset, $limit);
    }

    public function countByCompanyIds(array $companyIds): int
    {
        if ($companyIds === []) {
            return 0;
        }

        return count(array_filter(
            $this->store,
            static fn(User $u): bool => $u->companyId !== null && in_array($u->companyId, $companyIds, true),
        ));
    }

    public function findPaginatedByShopIds(array $shopIds, ?string $companyId, int $limit, int $offset): array
    {
        if ($shopIds === []) {
            return [];
        }

        $users = array_values(array_filter(
            $this->store,
            static fn(User $u): bool => $u->shopId !== null
                && in_array($u->shopId, $shopIds, true)
                && ($companyId === null || $u->companyId === $companyId),
        ));
        usort($users, static fn(User $a, User $b): int => strcmp($a->email->value, $b->email->value));

        return array_slice($users, $offset, $limit);
    }

    public function countByShopIds(array $shopIds, ?string $companyId): int
    {
        if ($shopIds === []) {
            return 0;
        }

        return count(array_filter(
            $this->store,
            static fn(User $u): bool => $u->shopId !== null
                && in_array($u->shopId, $shopIds, true)
                && ($companyId === null || $u->companyId === $companyId),
        ));
    }
}
