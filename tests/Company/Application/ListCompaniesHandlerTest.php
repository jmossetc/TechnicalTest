<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use DateTimeImmutable;
use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Application\Handler\ListCompaniesHandler;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySortCriteria;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use Mossetc\TechnicalTest\Tests\Support\InMemoryCompanyRepository;
use PHPUnit\Framework\TestCase;

final class ListCompaniesHandlerTest extends TestCase
{
    private InMemoryCompanyRepository $repository;
    private ListCompaniesHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCompanyRepository();
        $this->handler    = new ListCompaniesHandler($this->repository);
    }

    private function create(
        string $name,
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $city = null,
        ?string $postalCode = null,
        ?string $country = null,
    ): void {
        (new CreateCompanyHandler($this->repository))->handle(new CreateCompany(
            name:        $name,
            email:       $email,
            phoneNumber: $phoneNumber,
            city:        $city,
            postalCode:  $postalCode,
            country:     $country,
        ));
    }

    public function testReturnsEmptyResultWhenNoCompanies(): void
    {
        $result = $this->handler->handle(new ListCompanies());

        $this->assertSame([], $result->companies);
        $this->assertSame(0, $result->total);
    }

    public function testReturnsAllCompanies(): void
    {
        $this->create('Alpha'); $this->create('Beta');

        $result = $this->handler->handle(new ListCompanies());

        $this->assertCount(2, $result->companies);
        $this->assertSame(2, $result->total);
    }

    public function testPaginationLimitIsRespected(): void
    {
        $this->create('A'); $this->create('B'); $this->create('C');

        $result = $this->handler->handle(new ListCompanies(page: 1, limit: 2));

        $this->assertCount(2, $result->companies);
        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->pages());
    }

    public function testSecondPageReturnsRemainder(): void
    {
        $this->create('Alpha'); $this->create('Beta'); $this->create('Gamma');

        $result = $this->handler->handle(new ListCompanies(page: 2, limit: 2));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Gamma', $result->companies[0]->name->value);
    }

    public function testResultsAreOrderedAlphabetically(): void
    {
        $this->create('Gamma'); $this->create('Alpha'); $this->create('Beta');

        $result = $this->handler->handle(new ListCompanies());
        $names  = array_map(fn($c) => $c->name->value, $result->companies);

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $names);
    }

    public function testNameFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha Corp'); $this->create('Alpha Industries'); $this->create('Beta Ltd');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(name: 'Alpha')));

        $this->assertCount(2, $result->companies);
        $this->assertSame(2, $result->total);
    }

    public function testNameFilterIsCaseInsensitive(): void
    {
        $this->create('Acme Corporation');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(name: 'ACME')));

        $this->assertCount(1, $result->companies);
    }

    public function testNameFilterWithNoMatchReturnsEmpty(): void
    {
        $this->create('Acme');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(name: 'Nonexistent')));

        $this->assertSame([], $result->companies);
        $this->assertSame(0, $result->total);
    }

    public function testSortByNameDescendingReturnsReverseAlphabeticalOrder(): void
    {
        $this->create('Alpha Corp'); $this->create('Gamma Corp'); $this->create('Beta Corp');

        $result = $this->handler->handle(new ListCompanies(
            criteria: new CompanySearchCriteria(),
            sort:     new CompanySortCriteria(
                field:     CompanySortField::Name,
                direction: SortDirection::Desc,
            ),
        ));

        $names = array_map(fn($c) => $c->name->value, $result->companies);
        $this->assertSame(['Gamma Corp', 'Beta Corp', 'Alpha Corp'], $names);
    }

    public function testSortByCreatedAtAscendingPreservesInsertionTimeOrder(): void
    {
        $this->create('First');
        $this->create('Second');

        $result = $this->handler->handle(new ListCompanies(
            sort: new CompanySortCriteria(
                field:     CompanySortField::CreatedAt,
                direction: SortDirection::Asc,
            ),
        ));

        $this->assertCount(2, $result->companies);
    }

    public function testEmailFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha', email: 'alpha@example.com');
        $this->create('Beta',  email: 'beta@example.com');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(email: 'alpha')));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Alpha', $result->companies[0]->name->value);
    }

    public function testEmailFilterExcludesCompaniesWithoutEmail(): void
    {
        $this->create('Alpha', email: 'alpha@example.com');
        $this->create('Beta');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(email: 'beta')));

        $this->assertSame([], $result->companies);
    }

    public function testPhoneNumberFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha', phoneNumber: '+33 1 23 45 67 89');
        $this->create('Beta',  phoneNumber: '+44 20 7946 0958');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(phoneNumber: '+33')));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Alpha', $result->companies[0]->name->value);
    }

    public function testCityFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha', city: 'Paris');
        $this->create('Beta',  city: 'London');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(city: 'paris')));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Alpha', $result->companies[0]->name->value);
    }

    public function testPostalCodeFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha', postalCode: '75001');
        $this->create('Beta',  postalCode: '69001');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(postalCode: '750')));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Alpha', $result->companies[0]->name->value);
    }

    public function testCountryFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha', country: 'France');
        $this->create('Beta',  country: 'UK');

        $result = $this->handler->handle(new ListCompanies(criteria: new CompanySearchCriteria(country: 'france')));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Alpha', $result->companies[0]->name->value);
    }

    public function testCombinedFiltersNarrowResults(): void
    {
        $this->create('Alpha France', city: 'Paris',  country: 'France');
        $this->create('Beta France',  city: 'Lyon',   country: 'France');
        $this->create('Alpha UK',     city: 'London', country: 'UK');

        $result = $this->handler->handle(new ListCompanies(
            criteria: new CompanySearchCriteria(name: 'Alpha', country: 'France'),
        ));

        $this->assertCount(1, $result->companies);
        $this->assertSame('Alpha France', $result->companies[0]->name->value);
    }

    public function testCreatedFromFilterExcludesEarlierCompanies(): void
    {
        (new CreateCompanyHandler($this->repository))->handle(new CreateCompany('Early'));
        $cutoff = new DateTimeImmutable('+1 hour');

        $result = $this->handler->handle(new ListCompanies(
            criteria: new CompanySearchCriteria(createdFrom: $cutoff),
        ));

        $this->assertSame([], $result->companies);
        $this->assertSame(0, $result->total);
    }

    public function testCreatedToFilterExcludesLaterCompanies(): void
    {
        (new CreateCompanyHandler($this->repository))->handle(new CreateCompany('Now'));
        $pastCutoff = new DateTimeImmutable('-1 hour');

        $result = $this->handler->handle(new ListCompanies(
            criteria: new CompanySearchCriteria(createdTo: $pastCutoff),
        ));

        $this->assertSame([], $result->companies);
        $this->assertSame(0, $result->total);
    }
}
