<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Infrastructure\Repository;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class CompanyRepository implements CompanyRepositoryInterface
{
    private const string SELECT_COLUMNS =
        'BIN_TO_UUID(id) AS id, name, created_at, updated_at, deleted_at';

    public function __construct(private PDO $pdo) {}

    public function save(Company $company): void
    {
        $stmt = $this->prepare(
            'INSERT INTO companies (id, name)
             VALUES (UUID_TO_BIN(:id), :name) AS new_row
             ON DUPLICATE KEY UPDATE name = new_row.name',
        );

        $stmt->execute([
            'id'   => $company->id->value,
            'name' => $company->name->value,
        ]);
    }

    public function findById(CompanyId $id): ?Company
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM companies WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL LIMIT 1',
        );
        $stmt->execute(['id' => $id->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findByName(CompanyName $name): ?Company
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM companies WHERE name = :name AND deleted_at IS NULL LIMIT 1',
        );
        $stmt->execute(['name' => $name->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findPaginated(int $limit, int $offset): array
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM companies WHERE deleted_at IS NULL ORDER BY name ASC LIMIT :limit OFFSET :offset',
        );
        $stmt->execute(['limit' => $limit, 'offset' => $offset]);

        $companies = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $companies[] = $this->hydrate($row);
        }

        return $companies;
    }

    public function count(): int
    {
        $stmt = $this->prepare('SELECT COUNT(*) FROM companies WHERE deleted_at IS NULL');
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    public function delete(CompanyId $id): void
    {
        $stmt = $this->prepare(
            'UPDATE companies SET deleted_at = NOW() WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL',
        );
        $stmt->execute(['id' => $id->value]);
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

    /**
     * @param array<mixed, mixed> $row
     */
    private function hydrate(array $row): Company
    {
        return new Company(
            id:        new CompanyId($this->col($row, 'id')),
            name:      new CompanyName($this->col($row, 'name')),
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
