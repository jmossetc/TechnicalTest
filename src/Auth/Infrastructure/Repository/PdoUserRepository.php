<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Repository;

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
    public function __construct(private PDO $pdo) {}

    public function save(User $user): void
    {
        $stmt = $this->prepare(
            'INSERT INTO users (id, email, password_hash)
             VALUES (:id, :email, :password_hash) AS new_row
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
            'SELECT id, email, password_hash FROM users WHERE email = :email LIMIT 1',
        );
        $stmt->execute(['email' => $email->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findById(UserId $id): ?User
    {
        $stmt = $this->prepare(
            'SELECT id, email, password_hash FROM users WHERE id = :id LIMIT 1',
        );
        $stmt->execute(['id' => $id->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
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
            id: new UserId($this->col($row, 'id')),
            email: new Email($this->col($row, 'email')),
            password: HashedPassword::fromHash($this->col($row, 'password_hash')),
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
}
