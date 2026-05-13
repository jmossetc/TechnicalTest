<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Repository;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Auth\Domain\Email;
use Mossetc\TechnicalTest\Auth\Domain\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Domain\UserId;
use Mossetc\TechnicalTest\Auth\Domain\UserRepositoryInterface;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class PdoUserRepository implements UserRepositoryInterface
{
    private const string SELECT_COLUMNS = 'BIN_TO_UUID(id) AS id, email, password_hash, created_at, updated_at, deleted_at';

    public function __construct(private PDO $pdo) {}

    public function save(User $user): void
    {
        $stmt = $this->prepare(
            'INSERT INTO users (id, email, password_hash)
             VALUES (UUID_TO_BIN(:id), :email, :password_hash) AS new_row
             ON DUPLICATE KEY UPDATE
                 email         = new_row.email,
                 password_hash = new_row.password_hash',
        );

        $stmt->execute([
            'id'            => $user->id->value,
            'email'         => $user->email->value,
            'password_hash' => $user->password->hash,
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

    public function findPaginated(int $limit, int $offset): array
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM users WHERE deleted_at IS NULL ORDER BY email ASC LIMIT :limit OFFSET :offset',
        );
        $stmt->execute(['limit' => $limit, 'offset' => $offset]);

        $users = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $users[] = $this->hydrate($row);
        }

        return $users;
    }

    public function count(): int
    {
        $stmt = $this->prepare('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL');
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    public function delete(UserId $id): void
    {
        $stmt = $this->prepare(
            'UPDATE users SET deleted_at = NOW() WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL',
        );
        $stmt->execute(['id' => $id->value]);
    }

    public function findPaginatedByIds(array $ids, int $limit, int $offset): array
    {
        if ($ids === []) {
            return [];
        }

        $binds = implode(',', array_fill(0, count($ids), 'UUID_TO_BIN(?)'));
        $stmt  = $this->pdo->prepare(
            'SELECT ' . self::SELECT_COLUMNS . "
             FROM users WHERE id IN ({$binds}) AND deleted_at IS NULL
             ORDER BY email ASC LIMIT ? OFFSET ?",
        );
        $stmt->execute([...$ids, $limit, $offset]);

        $users = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $users[] = $this->hydrate($row);
        }

        return $users;
    }

    public function countByIds(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $binds = implode(',', array_fill(0, count($ids), 'UUID_TO_BIN(?)'));
        $stmt  = $this->pdo->prepare(
            "SELECT COUNT(*) FROM users WHERE id IN ({$binds}) AND deleted_at IS NULL",
        );
        $stmt->execute($ids);

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    private function prepare(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException("Failed to prepare statement: {$sql}");
        }

        return $stmt;
    }

    /**
     * @param array<mixed, mixed> $row
     */
    private function hydrate(array $row): User
    {
        return new User(
            id:        new UserId($this->col($row, 'id')),
            email:     new Email($this->col($row, 'email')),
            password:  HashedPassword::fromHash($this->col($row, 'password_hash')),
            createdAt: $this->parseDateTime($this->col($row, 'created_at')),
            updatedAt: $this->parseDateTime($this->col($row, 'updated_at')),
            deletedAt: $this->parseDateTimeNullable($row['deleted_at'] ?? null),
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
