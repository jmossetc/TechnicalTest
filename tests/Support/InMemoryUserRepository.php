<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Support;

use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;

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
}
