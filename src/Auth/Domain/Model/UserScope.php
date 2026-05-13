<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Domain\Model;

/**
 * Describes what subset of users a caller is permitted to see.
 *
 * Three variants, constructed via named factory methods:
 *   UserScope::all()                          – no restriction (admins)
 *   UserScope::companies($ids)                – users whose company_id is in $ids
 *   UserScope::shops($ids)                    – users whose shop_id is in $ids
 *   UserScope::shops($ids, $scopeCompanyId)   – same, plus AND company_id = $scopeCompanyId
 *                                               (used when a company_admin filters by shops)
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
        public ?string $scopeCompanyId = null,
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
    public static function shops(array $ids, ?string $companyId = null): self
    {
        return new self('shops', $ids, $companyId);
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
