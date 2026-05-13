<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Repository;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserRole;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRoleRepositoryInterface;
use PDO;

final readonly class UserRoleRepository implements UserRoleRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function findByUserId(UserId $userId): array
    {
        $roles = [];

        // Admin roles
        $stmt = $this->pdo->prepare('SELECT 1 FROM user_admin_roles WHERE user_id = UUID_TO_BIN(:id)');
        $stmt->execute(['id' => $userId->value]);
        if ($stmt->fetch() !== false) {
            $roles[] = new UserRole(Role::Admin);
        }

        // Company roles
        $stmt = $this->pdo->prepare('SELECT BIN_TO_UUID(company_id) AS company_id FROM user_company_roles WHERE user_id = UUID_TO_BIN(:id)');
        $stmt->execute(['id' => $userId->value]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row) && is_string($row['company_id'])) {
                $roles[] = new UserRole(Role::CompanyManager, companyId: $row['company_id']);
            }
        }

        // Shop roles
        $stmt = $this->pdo->prepare('SELECT BIN_TO_UUID(shop_id) AS shop_id FROM user_shop_roles WHERE user_id = UUID_TO_BIN(:id)');
        $stmt->execute(['id' => $userId->value]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row) && is_string($row['shop_id'])) {
                $roles[] = new UserRole(Role::ShopManager, shopId: $row['shop_id']);
            }
        }

        return $roles;
    }

    public function grantRole(UserId $userId, UserRole $role): void
    {
        match ($role->role) {
            Role::Admin => $this->pdo->prepare(
                'INSERT IGNORE INTO user_admin_roles (user_id) VALUES (UUID_TO_BIN(:user_id))',
            )->execute(['user_id' => $userId->value]),

            Role::CompanyManager => $this->pdo->prepare(
                'INSERT IGNORE INTO user_company_roles (user_id, company_id) VALUES (UUID_TO_BIN(:user_id), UUID_TO_BIN(:company_id))',
            )->execute(['user_id' => $userId->value, 'company_id' => $role->companyId]),

            Role::ShopManager => $this->pdo->prepare(
                'INSERT IGNORE INTO user_shop_roles (user_id, shop_id) VALUES (UUID_TO_BIN(:user_id), UUID_TO_BIN(:shop_id))',
            )->execute(['user_id' => $userId->value, 'shop_id' => $role->shopId]),
        };
    }

    public function findUserIdsByCompanyIds(array $companyIds): array
    {
        if ($companyIds === []) {
            return [];
        }

        $binds = implode(',', array_fill(0, count($companyIds), 'UUID_TO_BIN(?)'));
        $sql   = "SELECT BIN_TO_UUID(ucr.user_id) AS user_id
                  FROM user_company_roles ucr
                  WHERE ucr.company_id IN ({$binds})
                  UNION
                  SELECT BIN_TO_UUID(usr.user_id) AS user_id
                  FROM user_shop_roles usr
                  JOIN shops s ON s.id = usr.shop_id
                  WHERE s.company_id IN ({$binds}) AND s.deleted_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$companyIds, ...$companyIds]);

        $ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row) && is_string($row['user_id'])) {
                $ids[] = $row['user_id'];
            }
        }

        return $ids;
    }

    public function findUserIdsByShopIds(array $shopIds): array
    {
        if ($shopIds === []) {
            return [];
        }

        $binds = implode(',', array_fill(0, count($shopIds), 'UUID_TO_BIN(?)'));
        $stmt  = $this->pdo->prepare(
            "SELECT BIN_TO_UUID(user_id) AS user_id FROM user_shop_roles WHERE shop_id IN ({$binds})",
        );
        $stmt->execute($shopIds);

        $ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($row) && is_string($row['user_id'])) {
                $ids[] = $row['user_id'];
            }
        }

        return $ids;
    }

    public function findCompanyIdByShopId(string $shopId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT BIN_TO_UUID(company_id) AS company_id FROM shops WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $shopId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row) || !is_string($row['company_id'])) {
            return null;
        }

        return $row['company_id'];
    }
}
