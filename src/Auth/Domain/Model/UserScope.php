<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

/**
 * Describes what subset of users a caller is permitted to see.
 *
 * Three variants, constructed via named factory methods:
 *   UserScope::all()            – no restriction (admins)
 *   UserScope::companies($ids)  – users associated with these companies
 *   UserScope::shops($ids)      – users associated with these shops
 *
 * The parameterless constructor (new UserScope()) produces the "all" variant
 * and may be used as a PHP 8.1+ default-parameter expression.
 */
final readonly class UserScope
{
    /** @var list<string> */
    public array $ids;

    /**
     * @param list<string> $ids
     */
    public function __construct(
        public string $kind = 'all',
        array $ids = [],
    ) {
        $this->ids = $ids;
    }

    public static function all(): self
    {
        return new self();
    }

    /**
     * @param list<string> $ids
     */
    public static function companies(array $ids): self
    {
        return new self('companies', $ids);
    }

    /**
     * @param list<string> $ids
     */
    public static function shops(array $ids): self
    {
        return new self('shops', $ids);
    }

    public function isAll(): bool
    {
        return $this->kind === 'all';
    }

    public function isCompanies(): bool
    {
        return $this->kind === 'companies';
    }

    public function isShops(): bool
    {
        return $this->kind === 'shops';
    }
}
