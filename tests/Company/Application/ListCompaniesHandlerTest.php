<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Application\Handler\ListCompaniesHandler;
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

    private function create(string $name): void
    {
        (new CreateCompanyHandler($this->repository))->handle(new CreateCompany($name));
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
        $this->assertSame(2,  $result->total);
    }

    public function testPaginationLimitIsRespected(): void
    {
        $this->create('A'); $this->create('B'); $this->create('C');

        $result = $this->handler->handle(new ListCompanies(page: 1, limit: 2));

        $this->assertCount(2, $result->companies);
        $this->assertSame(3,  $result->total);
        $this->assertSame(2,  $result->pages());
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

        $names = array_map(fn($c) => $c->name->value, $result->companies);
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $names);
    }

    public function testNameFilterReturnsMatchingCompanies(): void
    {
        $this->create('Alpha Corp'); $this->create('Alpha Industries'); $this->create('Beta Ltd');

        $result = $this->handler->handle(new ListCompanies(name: 'Alpha'));

        $this->assertCount(2, $result->companies);
        $this->assertSame(2,  $result->total);
    }

    public function testNameFilterIsCaseInsensitive(): void
    {
        $this->create('Acme Corporation');

        $result = $this->handler->handle(new ListCompanies(name: 'ACME'));

        $this->assertCount(1, $result->companies);
    }

    public function testNameFilterWithNoMatchReturnsEmpty(): void
    {
        $this->create('Acme');

        $result = $this->handler->handle(new ListCompanies(name: 'Nonexistent'));

        $this->assertSame([], $result->companies);
        $this->assertSame(0,  $result->total);
    }
}
