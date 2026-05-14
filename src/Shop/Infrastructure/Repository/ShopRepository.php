<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Infrastructure\Repository;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyId;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopAddress;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopName;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class ShopRepository implements ShopRepositoryInterface
{
    private const string SELECT_COLUMNS =
        'BIN_TO_UUID(id) AS id, BIN_TO_UUID(company_id) AS company_id, name,
         email, phone_number, address_line_1, address_line_2, city, postal_code, country,
         latitude, longitude, is_digital, is_active, created_at, updated_at, deleted_at';

    public function __construct(private PDO $pdo) {}

    public function save(Shop $shop): void
    {
        $stmt = $this->prepare(
            'INSERT INTO shops
                 (id, company_id, name, email, phone_number,
                  address_line_1, address_line_2, city, postal_code, country,
                  latitude, longitude, is_digital, is_active)
             VALUES
                 (UUID_TO_BIN(:id), UUID_TO_BIN(:company_id), :name, :email, :phone_number,
                  :address_line_1, :address_line_2, :city, :postal_code, :country,
                  :latitude, :longitude, :is_digital, :is_active)
             AS new_row
             ON DUPLICATE KEY UPDATE
                 name          = new_row.name,
                 email         = new_row.email,
                 phone_number  = new_row.phone_number,
                 address_line_1 = new_row.address_line_1,
                 address_line_2 = new_row.address_line_2,
                 city          = new_row.city,
                 postal_code   = new_row.postal_code,
                 country       = new_row.country,
                 latitude      = new_row.latitude,
                 longitude     = new_row.longitude,
                 is_digital    = new_row.is_digital,
                 is_active     = new_row.is_active',
        );

        $stmt->execute([
            'id'            => $shop->id->value,
            'company_id'    => $shop->companyId->value,
            'name'          => $shop->name->value,
            'email'         => $shop->email,
            'phone_number'  => $shop->phoneNumber,
            'address_line_1'=> $shop->address->addressLine1,
            'address_line_2'=> $shop->address->addressLine2,
            'city'          => $shop->address->city,
            'postal_code'   => $shop->address->postalCode,
            'country'       => $shop->address->country,
            'latitude'      => $shop->latitude,
            'longitude'     => $shop->longitude,
            'is_digital'    => $shop->isDigital ? 1 : 0,
            'is_active'     => $shop->isActive  ? 1 : 0,
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

    public function findPaginatedByCriteria(
        ShopSearchCriteria $criteria,
        ShopSortCriteria   $sort,
        int                $limit,
        int                $offset,
    ): array {
        [$where, $params] = $this->buildWhereClause($criteria);

        $stmt = $this->prepare(
            'SELECT ' . self::SELECT_COLUMNS . "
             FROM shops WHERE {$where} ORDER BY {$sort->field->value} {$sort->direction->value} LIMIT ? OFFSET ?",
        );
        $stmt->execute([...$params, $limit, $offset]);

        $shops = [];
        while (is_array($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            $shops[] = $this->hydrate($row);
        }

        return $shops;
    }

    public function countByCriteria(ShopSearchCriteria $criteria): int
    {
        [$where, $params] = $this->buildWhereClause($criteria);

        $stmt = $this->prepare("SELECT COUNT(*) FROM shops WHERE {$where}");
        $stmt->execute($params);

        $count = $stmt->fetchColumn();

        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * @return array{string, list<mixed>}
     */
    private function buildWhereClause(ShopSearchCriteria $criteria): array
    {
        $conditions = ['deleted_at IS NULL'];
        /** @var list<mixed> $params */
        $params = [];

        if ($criteria->companyId !== null) {
            $conditions[] = 'company_id = UUID_TO_BIN(?)';
            $params[]     = $criteria->companyId;
        }

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

        if ($criteria->isDigital !== null) {
            $conditions[] = 'is_digital = ?';
            $params[]     = $criteria->isDigital ? 1 : 0;
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

    public function delete(ShopId $id): void
    {
        $this->prepare(
            'UPDATE shops SET deleted_at = NOW() WHERE id = UUID_TO_BIN(:id) AND deleted_at IS NULL',
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
    private function hydrate(array $row): Shop
    {
        $lat = $row['latitude'] ?? null;
        $lng = $row['longitude'] ?? null;

        return new Shop(
            id:          new ShopId($this->col($row, 'id')),
            companyId:   new CompanyId($this->col($row, 'company_id')),
            name:        new ShopName($this->col($row, 'name')),
            address:     new ShopAddress(
                addressLine1: $this->nullable($row['address_line_1'] ?? null),
                addressLine2: $this->nullable($row['address_line_2'] ?? null),
                city:         $this->nullable($row['city'] ?? null),
                postalCode:   $this->nullable($row['postal_code'] ?? null),
                country:      $this->nullable($row['country'] ?? null),
            ),
            email:       $this->nullable($row['email'] ?? null),
            phoneNumber: $this->nullable($row['phone_number'] ?? null),
            latitude:    is_numeric($lat) ? (float) $lat : null,
            longitude:   is_numeric($lng) ? (float) $lng : null,
            isDigital:   (bool) ($row['is_digital'] ?? false),
            isActive:    (bool) ($row['is_active']  ?? true),
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
