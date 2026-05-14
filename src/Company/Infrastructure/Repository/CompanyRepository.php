<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Infrastructure\Repository;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
use Mossetc\TechnicalTest\Company\Domain\Repository\CompanyRepositoryInterface;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class CompanyRepository implements CompanyRepositoryInterface
{
    private const string SELECT_COLUMNS =
        'BIN_TO_UUID(id) AS id, name, email, phone_number, website,
         address_line_1, address_line_2, city, postal_code, country,
         is_active, created_at, updated_at, deleted_at';

    public function __construct(private PDO $pdo) {}

    public function save(Company $company): void
    {
        $stmt = $this->prepare(
            'INSERT INTO companies
                 (id, name, email, phone_number, website,
                  address_line_1, address_line_2, city, postal_code, country, is_active)
             VALUES
                 (UUID_TO_BIN(:id), :name, :email, :phone_number, :website,
                  :address_line_1, :address_line_2, :city, :postal_code, :country, :is_active)
             AS new_row
             ON DUPLICATE KEY UPDATE
                 name          = new_row.name,
                 email         = new_row.email,
                 phone_number  = new_row.phone_number,
                 website       = new_row.website,
                 address_line_1 = new_row.address_line_1,
                 address_line_2 = new_row.address_line_2,
                 city          = new_row.city,
                 postal_code   = new_row.postal_code,
                 country       = new_row.country,
                 is_active     = new_row.is_active',
        );

        $stmt->execute([
            'id'            => $company->id->value,
            'name'          => $company->name->value,
            'email'         => $company->email,
            'phone_number'  => $company->phoneNumber,
            'website'       => $company->website,
            'address_line_1'=> $company->addressLine1,
            'address_line_2'=> $company->addressLine2,
            'city'          => $company->city,
            'postal_code'   => $company->postalCode,
            'country'       => $company->country,
            'is_active'     => $company->isActive ? 1 : 0,
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

    public function findPaginatedByCriteria(CompanySearchCriteria $criteria, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhereClause($criteria);

        $stmt = $this->pdo->prepare(
            'SELECT ' . self::SELECT_COLUMNS . "
         FROM companies WHERE {$where} ORDER BY name ASC LIMIT ? OFFSET ?",
        );
        $stmt->execute([...$params, $limit, $offset]);

        $companies = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $companies[] = $this->hydrate($row);
        }

        return $companies;
    }

    public function countByCriteria(CompanySearchCriteria $criteria): int
    {
        [$where, $params] = $this->buildWhereClause($criteria);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM companies WHERE {$where}");
        $stmt->execute($params);

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * @return array{string, list<mixed>}
     */
    private function buildWhereClause(CompanySearchCriteria $criteria): array
    {
        $conditions = ['deleted_at IS NULL'];
        /** @var list<mixed> $params */
        $params = [];

        if ($criteria->name !== null) {
            $conditions[] = 'LOWER(name) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->name . '%';
        }

        if ($criteria->email !== null) {
            $conditions[] = 'LOWER(email) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->email . '%';
        }

        if ($criteria->phoneNumber !== null) {
            $conditions[] = 'LOWER(phone_number) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->phoneNumber . '%';
        }

        if ($criteria->city !== null) {
            $conditions[] = 'LOWER(city) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->city . '%';
        }

        if ($criteria->postalCode !== null) {
            $conditions[] = 'LOWER(postal_code) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->postalCode . '%';
        }

        if ($criteria->country !== null) {
            $conditions[] = 'LOWER(country) LIKE LOWER(?)';
            $params[]     = '%' . $criteria->country . '%';
        }

        if ($criteria->createdFrom !== null) {
            $conditions[] = 'created_at >= ?';
            $params[]     = $criteria->createdFrom->format('Y-m-d H:i:s');
        }

        if ($criteria->createdTo !== null) {
            $conditions[] = 'created_at <= ?';
            $params[]     = $criteria->createdTo->format('Y-m-d H:i:s');
        }

        return [implode(' AND ', $conditions), $params];
    }

    public function delete(CompanyId $id): void
    {
        $this->prepare(
            'UPDATE companies SET deleted_at = NOW() WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL',
        )->execute(['id' => $id->value]);
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
            id:           new CompanyId($this->col($row, 'id')),
            name:         new CompanyName($this->col($row, 'name')),
            email:        $this->nullable($row['email'] ?? null),
            phoneNumber:  $this->nullable($row['phone_number'] ?? null),
            website:      $this->nullable($row['website'] ?? null),
            addressLine1: $this->nullable($row['address_line_1'] ?? null),
            addressLine2: $this->nullable($row['address_line_2'] ?? null),
            city:         $this->nullable($row['city'] ?? null),
            postalCode:   $this->nullable($row['postal_code'] ?? null),
            country:      $this->nullable($row['country'] ?? null),
            isActive:     (bool) ($row['is_active'] ?? true),
            createdAt:    $this->parseDateTime($this->col($row, 'created_at')),
            updatedAt:    $this->parseDateTime($this->col($row, 'updated_at')),
            deletedAt:    $this->parseDateTimeNullable($row['deleted_at'] ?? null),
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

    private function nullable(mixed $value): ?string
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
