<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Application;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Application\Handler\ListShopsHandler;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Tests\Support\InMemoryShopRepository;
use PHPUnit\Framework\TestCase;

final class ListShopsHandlerTest extends TestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string COMPANY_B = '22222222-2222-4222-8222-222222222222';

    private InMemoryShopRepository $repository;
    private ListShopsHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryShopRepository();
        $this->handler    = new ListShopsHandler($this->repository);
    }

    private function create(
        string $companyId = self::COMPANY_A,
        string $name = 'Test Shop',
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $city = null,
        ?string $postalCode = null,
        ?string $country = null,
        bool $isDigital = false,
    ): void {
        (new CreateShopHandler($this->repository))->handle(new CreateShop(
            companyId:   $companyId,
            name:        $name,
            email:       $email,
            phoneNumber: $phoneNumber,
            city:        $city,
            postalCode:  $postalCode,
            country:     $country,
            isDigital:   $isDigital,
        ));
    }

    public function testReturnsEmptyResultWhenNoShops(): void
    {
        $result = $this->handler->handle(new ListShops());

        $this->assertSame([], $result->shops);
        $this->assertSame(0, $result->total);
    }

    public function testReturnsAllShopsWhenNoCriteriaSet(): void
    {
        $this->create(self::COMPANY_A, 'Alpha');
        $this->create(self::COMPANY_B, 'Beta');

        $result = $this->handler->handle(new ListShops());

        $this->assertCount(2, $result->shops);
        $this->assertSame(2, $result->total);
    }

    public function testPaginationLimitIsRespected(): void
    {
        $this->create(name: 'A');
        $this->create(name: 'B');
        $this->create(name: 'C');

        $result = $this->handler->handle(new ListShops(page: 1, limit: 2));

        $this->assertCount(2, $result->shops);
        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->pages());
    }

    public function testSecondPageReturnsRemainder(): void
    {
        $this->create(name: 'Alpha');
        $this->create(name: 'Beta');
        $this->create(name: 'Gamma');

        $result = $this->handler->handle(new ListShops(page: 2, limit: 2));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Gamma', $result->shops[0]->name->value);
    }

    public function testResultsAreOrderedAlphabetically(): void
    {
        $this->create(name: 'Gamma');
        $this->create(name: 'Alpha');
        $this->create(name: 'Beta');

        $result = $this->handler->handle(new ListShops());
        $names  = array_map(fn($s) => $s->name->value, $result->shops);

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $names);
    }

    public function testCompanyIdFilterScopesToOneCompany(): void
    {
        $this->create(self::COMPANY_A, 'Alpha Shop');
        $this->create(self::COMPANY_B, 'Beta Shop');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(companyId: self::COMPANY_A),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Alpha Shop', $result->shops[0]->name->value);
    }

    public function testNameFilterReturnsMatchingShops(): void
    {
        $this->create(name: 'Flagship Store');
        $this->create(name: 'Flagship Outlet');
        $this->create(name: 'Corner Shop');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(name: 'Flagship'),
        ));

        $this->assertCount(2, $result->shops);
        $this->assertSame(2, $result->total);
    }

    public function testNameFilterIsCaseInsensitive(): void
    {
        $this->create(name: 'Main Street Store');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(name: 'MAIN STREET'),
        ));

        $this->assertCount(1, $result->shops);
    }

    public function testEmailFilterReturnsMatchingShops(): void
    {
        $this->create(name: 'Alpha', email: 'alpha@store.com');
        $this->create(name: 'Beta',  email: 'beta@store.com');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(email: 'alpha'),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Alpha', $result->shops[0]->name->value);
    }

    public function testPhoneNumberFilterReturnsMatchingShops(): void
    {
        $this->create(name: 'Alpha', phoneNumber: '+33 1 23 45 67 89');
        $this->create(name: 'Beta',  phoneNumber: '+44 20 7946 0958');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(phoneNumber: '+33'),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Alpha', $result->shops[0]->name->value);
    }

    public function testCityFilterReturnsMatchingShops(): void
    {
        $this->create(name: 'Paris Shop',  city: 'Paris');
        $this->create(name: 'London Shop', city: 'London');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(city: 'paris'),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Paris Shop', $result->shops[0]->name->value);
    }

    public function testPostalCodeFilterReturnsMatchingShops(): void
    {
        $this->create(name: 'Alpha', postalCode: '75001');
        $this->create(name: 'Beta',  postalCode: '69001');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(postalCode: '750'),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Alpha', $result->shops[0]->name->value);
    }

    public function testCountryFilterReturnsMatchingShops(): void
    {
        $this->create(name: 'Alpha', country: 'France');
        $this->create(name: 'Beta',  country: 'UK');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(country: 'france'),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Alpha', $result->shops[0]->name->value);
    }

    public function testIsDigitalTrueFilterReturnsOnlyDigitalShops(): void
    {
        $this->create(name: 'Physical Store', isDigital: false);
        $this->create(name: 'Online Shop',    isDigital: true);

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(isDigital: true),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Online Shop', $result->shops[0]->name->value);
    }

    public function testIsDigitalFalseFilterReturnsOnlyPhysicalShops(): void
    {
        $this->create(name: 'Physical Store', isDigital: false);
        $this->create(name: 'Online Shop',    isDigital: true);

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(isDigital: false),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Physical Store', $result->shops[0]->name->value);
    }

    public function testCombinedFiltersNarrowResults(): void
    {
        $this->create(self::COMPANY_A, 'Flagship Paris',  city: 'Paris',  country: 'France');
        $this->create(self::COMPANY_A, 'Flagship London', city: 'London', country: 'UK');
        $this->create(self::COMPANY_B, 'Corner Paris',    city: 'Paris',  country: 'France');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(companyId: self::COMPANY_A, city: 'Paris'),
        ));

        $this->assertCount(1, $result->shops);
        $this->assertSame('Flagship Paris', $result->shops[0]->name->value);
    }

    public function testCreatedFromFilterExcludesEarlierShops(): void
    {
        (new CreateShopHandler($this->repository))->handle(new CreateShop(self::COMPANY_A, 'Early'));
        $cutoff = new DateTimeImmutable('+1 hour');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(createdFrom: $cutoff),
        ));

        $this->assertSame([], $result->shops);
        $this->assertSame(0, $result->total);
    }

    public function testCreatedToFilterExcludesLaterShops(): void
    {
        (new CreateShopHandler($this->repository))->handle(new CreateShop(self::COMPANY_A, 'Now'));
        $pastCutoff = new DateTimeImmutable('-1 hour');

        $result = $this->handler->handle(new ListShops(
            criteria: new ShopSearchCriteria(createdTo: $pastCutoff),
        ));

        $this->assertSame([], $result->shops);
        $this->assertSame(0, $result->total);
    }
}
