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
        $scope  = $command->scope;

        if ($scope->isCompanies()) {
            $users = $this->repository->findPaginatedByCompanyIds($scope->ids, $command->limit, $offset);
            $total = $this->repository->countByCompanyIds($scope->ids);
        } elseif ($scope->isShops()) {
            $users = $this->repository->findPaginatedByShopIds($scope->ids, $scope->scopeCompanyId, $command->limit, $offset);
            $total = $this->repository->countByShopIds($scope->ids, $scope->scopeCompanyId);
        } else {
            $users = $this->repository->findPaginated($command->limit, $offset);
            $total = $this->repository->count();
        }

        return new PaginatedUsers(
            users: $users,
            total: $total,
            page:  $command->page,
            limit: $command->limit,
        );
    }
}
