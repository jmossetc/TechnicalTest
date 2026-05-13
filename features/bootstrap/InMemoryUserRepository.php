<?php

declare(strict_types=1);

use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;

/**
 * In-memory implementation of UserRepositoryInterface for Behat scenarios.
 * Lives in features/bootstrap/ so it is never shipped to production.
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

    public function delete(UserId $id): void
    {
        unset($this->store[$id->value]);
    }

    public function findPaginatedByIds(array $ids, int $limit, int $offset): array
    {
        if ($ids === []) {
            return [];
        }

        $users = array_values(array_filter(
            $this->store,
            static fn(User $u): bool => in_array($u->id->value, $ids, true),
        ));
        usort($users, static fn(User $a, User $b): int => strcmp($a->email->value, $b->email->value));

        return array_slice($users, $offset, $limit);
    }

    public function countByIds(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return count(array_filter(
            $this->store,
            static fn(User $u): bool => in_array($u->id->value, $ids, true),
        ));
    }
}
