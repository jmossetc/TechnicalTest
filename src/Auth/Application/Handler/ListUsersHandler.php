<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Query\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\Query\PaginatedUsers;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;

final readonly class ListUsersHandler
{
    public function __construct(private UserRepositoryInterface $repository) {}

    public function handle(ListUsers $query): PaginatedUsers
    {
        $offset = ($query->page - 1) * $query->limit;

        return new PaginatedUsers(
            users: $this->repository->findPaginated($query->limit, $offset),
            total: $this->repository->count(),
            page: $query->page,
            limit: $query->limit,
        );
    }
}
