<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Infrastructure\Repository;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class ShopRepository implements ShopRepositoryInterface
{
    private const string SELECT_COLUMNS =
        'BIN_TO_UUID(id) AS id, BIN_TO_UUID(company_id) AS company_id, name,
         street, city, zip, country, created_at, updated_at, deleted_at';

    public function __construct(private PDO $pdo) {}

    public function save(Shop $shop): void
    {
        $stmt = $this->prepare(
            'INSERT INTO shops (id, company_id, name, street, city, zip, country)
             VALUES (UUID_TO_BIN(:id), UUID_TO_BIN(:company_id), :name, :street, :city, :zip, :country) AS new_row
             ON DUPLICATE KEY UPDATE
                 name    = new_row.name,
                 street  = new_row.street,
                 city    = new_row.city,
                 zip     = new_row.zip,
                 country = new_row.country',
        );

        $stmt->execute([
            'id'         => $shop->id->value,
            'company_id' => $shop->companyId->value,
            'name'       => $shop->name->value,
            'street'     => $shop->address->street,
            'city'       => $shop->address->city,
            'zip'        => $shop->address->zip,
            'country'    => $shop->address->country,
        ]);
    }

    public function findById(ShopId $id): ?Shop
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM shops WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL LIMIT 1',
        );
        $stmt->execute(['id' => $id->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findByNameAndCompany(ShopName $name, CompanyId $companyId): ?Shop
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM shops WHERE name = :name AND company_id = UUID_TO_BIN(:company_id) AND deleted_at IS NULL LIMIT 1',
        );
        $stmt->execute(['name' => $name->value, 'company_id' => $companyId->value]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findPaginatedByCompany(CompanyId $companyId, int $limit, int $offset): array
    {
        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . '
             FROM shops WHERE company_id = UUID_TO_BIN(:company_id) AND deleted_at IS NULL
             ORDER BY name ASC LIMIT :limit OFFSET :offset',
        );
        $stmt->execute(['company_id' => $companyId->value, 'limit' => $limit, 'offset' => $offset]);

        $shops = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $shops[] = $this->hydrate($row);
        }

        return $shops;
    }

    public function countByCompany(CompanyId $companyId): int
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) FROM shops WHERE company_id = UUID_TO_BIN(:company_id) AND deleted_at IS NULL',
        );
        $stmt->execute(['company_id' => $companyId->value]);

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    public function delete(ShopId $id): void
    {
        $stmt = $this->prepare(
            'UPDATE shops SET deleted_at = NOW() WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL',
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
    private function hydrate(array $row): Shop
    {
        return new Shop(
            id:        new ShopId($this->col($row, 'id')),
            companyId: new CompanyId($this->col($row, 'company_id')),
            name:      new ShopName($this->col($row, 'name')),
            address:   new ShopAddress(
                street:  $this->nullable($row['street'] ?? null),
                city:    $this->nullable($row['city'] ?? null),
                zip:     $this->nullable($row['zip'] ?? null),
                country: $this->nullable($row['country'] ?? null),
            ),
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
