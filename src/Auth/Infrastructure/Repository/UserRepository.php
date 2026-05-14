<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Repository;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserScope;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class UserRepository implements UserRepositoryInterface
{
    private const string SELECT_COLUMNS =
        'BIN_TO_UUID(id) AS id, email, first_name, last_name, phone_number,
         role, BIN_TO_UUID(company_id) AS company_id, BIN_TO_UUID(shop_id) AS shop_id,
         is_active, last_login_at, password_hash, created_at, updated_at, deleted_at';

    public function __construct(private PDO $pdo) {}

    public function save(User $user): void
    {
        $stmt = $this->prepare(
            'INSERT INTO users
                 (id, email, first_name, last_name, phone_number,
                  role, company_id, shop_id, is_active, password_hash)
             VALUES
                 (UUID_TO_BIN(:id), :email, :first_name, :last_name, :phone_number,
                  :role, UUID_TO_BIN(:company_id), UUID_TO_BIN(:shop_id), :is_active, :password_hash)
             AS new_row
             ON DUPLICATE KEY UPDATE
                 email         = new_row.email,
                 first_name    = new_row.first_name,
                 last_name     = new_row.last_name,
                 phone_number  = new_row.phone_number,
                 role          = new_row.role,
                 company_id    = new_row.company_id,
                 shop_id       = new_row.shop_id,
                 is_active     = new_row.is_active,
                 password_hash = new_row.password_hash',
        );

        $stmt->execute([
            'id'           => $user->id->value,
            'email'        => $user->email->value,
            'first_name'   => $user->firstName->value,
            'last_name'    => $user->lastName->value,
            'phone_number' => $user->phoneNumber,
            'role'         => $user->role->value,
            'company_id'   => $user->companyId,
            'shop_id'      => $user->shopId,
            'is_active'    => $user->isActive ? 1 : 0,
            'password_hash'=> $user->password->hash,
        ]);
    }

    public function findByEmail(Email $email): ?User
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1',
        );
        $stmt->execute(['email' => $email->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findById(UserId $id): ?User
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM users WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL LIMIT 1',
        );
        $stmt->execute(['id' => $id->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function delete(UserId $id): void
    {
        $this->prepare(
            'UPDATE users SET deleted_at = NOW() WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL',
        )->execute(['id' => $id->value]);
    }

    public function updateLastLogin(UserId $id): void
    {
        $this->prepare(
            'UPDATE users SET last_login_at = NOW() WHERE id = UUID_TO_BIN(:id)',
        )->execute(['id' => $id->value]);
    }

    public function findPaginatedByCriteria(
        UserSearchCriteria $criteria,
        UserScope $scope,
        int $limit,
        int $offset,
    ): array {
        [$where, $params] = $this->buildWhereClause($criteria, $scope);

        $stmt = $this->pdo->prepare(
            'SELECT ' . self::SELECT_COLUMNS . "
             FROM users WHERE {$where} ORDER BY email ASC LIMIT ? OFFSET ?",
        );
        $stmt->execute([...$params, $limit, $offset]);

        return $this->fetchAll($stmt);
    }

    public function countByCriteria(UserSearchCriteria $criteria, UserScope $scope): int
    {
        [$where, $params] = $this->buildWhereClause($criteria, $scope);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
        $stmt->execute($params);

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * @return array{string, list<mixed>}
     */
    private function buildWhereClause(UserSearchCriteria $criteria, UserScope $scope): array
    {
        $conditions = ['deleted_at IS NULL'];
        /** @var list<mixed> $params */
        $params = [];

        if ($scope->isCompanies()) {
            if ($scope->ids === []) {
                $conditions[] = '1=0';
            } else {
                $in           = implode(',', array_fill(0, count($scope->ids), 'UUID_TO_BIN(?)'));
                $conditions[] = "company_id IN ({$in})";
                array_push($params, ...$scope->ids);
            }
        } elseif ($scope->isShops()) {
            if ($scope->ids === []) {
                $conditions[] = '1=0';
            } else {
                $in           = implode(',', array_fill(0, count($scope->ids), 'UUID_TO_BIN(?)'));
                $conditions[] = "shop_id IN ({$in})";
                array_push($params, ...$scope->ids);

                if ($scope->scopeCompanyId !== null) {
                    $conditions[] = 'company_id = UUID_TO_BIN(?)';
                    $params[]     = $scope->scopeCompanyId;
                }
            }
        }

        if ($criteria->email !== null) {
            $conditions[] = 'LOWER(email) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->email . '%';
        }

        if ($criteria->firstName !== null) {
            $conditions[] = 'LOWER(first_name) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->firstName . '%';
        }

        if ($criteria->lastName !== null) {
            $conditions[] = 'LOWER(last_name) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->lastName . '%';
        }

        if ($criteria->phoneNumber !== null) {
            $conditions[] = 'LOWER(phone_number) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->phoneNumber . '%';
        }

        if ($criteria->role !== null) {
            $conditions[] = 'role = ?';
            $params[]     = $criteria->role->value;
        }

        if ($criteria->isActive !== null) {
            $conditions[] = 'is_active = ?';
            $params[]     = $criteria->isActive ? 1 : 0;
        }

        if ($criteria->createdFrom !== null) {
            $conditions[] = 'created_at >= ?';
            $params[]     = $criteria->createdFrom->format('Y-m-d H:i:s');
        }

        if ($criteria->createdTo !== null) {
            $conditions[] = 'created_at <= ?';
            $params[]     = $criteria->createdTo->format('Y-m-d H:i:s');
        }

        if ($criteria->lastLoginFrom !== null) {
            $conditions[] = 'last_login_at >= ?';
            $params[]     = $criteria->lastLoginFrom->format('Y-m-d H:i:s');
        }

        if ($criteria->lastLoginTo !== null) {
            $conditions[] = 'last_login_at <= ?';
            $params[]     = $criteria->lastLoginTo->format('Y-m-d H:i:s');
        }

        return [implode(' AND ', $conditions), $params];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function prepare(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException("Failed to prepare statement: {$sql}");
        }

        return $stmt;
    }

    /** @return list<User> */
    private function fetchAll(PDOStatement $stmt): array
    {
        $users = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $users[] = $this->hydrate($row);
        }

        return $users;
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function hydrate(array $row): User
    {
        return new User(
            id:          new UserId($this->col($row, 'id')),
            email:       new Email($this->col($row, 'email')),
            password:    HashedPassword::fromHash($this->col($row, 'password_hash')),
            firstName:   new FirstName($this->col($row, 'first_name')),
            lastName:    new LastName($this->col($row, 'last_name')),
            role:        Role::from($this->col($row, 'role')),
            companyId:   $this->nullableStr($row['company_id'] ?? null),
            shopId:      $this->nullableStr($row['shop_id'] ?? null),
            phoneNumber: $this->nullableStr($row['phone_number'] ?? null),
            isActive:    (bool) ($row['is_active'] ?? true),
            lastLoginAt: $this->parseDateTimeNullable($row['last_login_at'] ?? null),
            createdAt:   $this->parseDateTime($this->col($row, 'created_at')),
            updatedAt:   $this->parseDateTime($this->col($row, 'updated_at')),
            deletedAt:   $this->parseDateTimeNullable($row['deleted_at'] ?? null),
        );
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function col(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        if (!is_string($value)) {
            throw new RuntimeException(
                sprintf("Expected string for column '%s', got %s", $key, get_debug_type($value)),
            );
        }

        return $value;
    }

    private function nullableStr(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function parseDateTime(string $value): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        if ($dt === false) {
            throw new RuntimeException("Cannot parse datetime: '{$value}'");
        }

        return $dt;
    }

    private function parseDateTimeNullable(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $this->parseDateTime($value);
    }
}
