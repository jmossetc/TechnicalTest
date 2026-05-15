<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\DTO\PaginatedUsers;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;

final readonly class ListUsersHandler
{
    public function __construct(private UserRepositoryInterface $repository) {}

    public function handle(ListUsers $command): PaginatedUsers
    {
        $offset = ($command->page - 1) * $command->limit;

        $users = $this->repository->findPaginatedByCriteria(
            $command->criteria,
            $command->scope,
            $command->sort,
            $command->limit,
            $offset,
        );

        $total = $this->repository->countByCriteria($command->criteria, $command->scope);

        return new PaginatedUsers(
            users: $users,
            total: $total,
            page: $command->page,
            limit: $command->limit,
        );
    }
}
