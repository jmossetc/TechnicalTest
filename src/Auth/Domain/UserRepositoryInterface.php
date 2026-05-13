<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findByEmail(Email $email): ?User;

    public function findById(UserId $id): ?User;

    /**
     * @return list<User>
     */
    public function findPaginated(int $limit, int $offset): array;

    public function count(): int;

    public function delete(UserId $id): void;
}
