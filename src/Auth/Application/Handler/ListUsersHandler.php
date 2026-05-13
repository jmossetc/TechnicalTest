<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Application\Handler;

use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\DTO\PaginatedUsers;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRoleRepositoryInterface;

final readonly class ListUsersHandler
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private UserRoleRepositoryInterface $roleRepository,
    ) {}

    public function handle(ListUsers $query): PaginatedUsers
    {
        $offset = ($query->page - 1) * $query->limit;

        if ($query->scope->isCompanies()) {
            $ids   = $this->roleRepository->findUserIdsByCompanyIds($query->scope->ids);
            $users = $this->repository->findPaginatedByIds($ids, $query->limit, $offset);
            $total = $this->repository->countByIds($ids);
        } elseif ($query->scope->isShops()) {
            $ids   = $this->roleRepository->findUserIdsByShopIds($query->scope->ids);
            $users = $this->repository->findPaginatedByIds($ids, $query->limit, $offset);
            $total = $this->repository->countByIds($ids);
        } else {
            $users = $this->repository->findPaginated($query->limit, $offset);
            $total = $this->repository->count();
        }

        return new PaginatedUsers(
            users: $users,
            total: $total,
            page: $query->page,
            limit: $query->limit,
        );
    }
}
