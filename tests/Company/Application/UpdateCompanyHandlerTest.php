<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Command\UpdateCompany;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Application\Handler\UpdateCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyAlreadyExistsException;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Tests\Support\InMemoryCompanyRepository;
use PHPUnit\Framework\TestCase;

final class UpdateCompanyHandlerTest extends TestCase
{
    private InMemoryCompanyRepository $repository;
    private UpdateCompanyHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCompanyRepository();
        $this->handler    = new UpdateCompanyHandler($this->repository);
    }

    private function create(string $name): string
    {
        return (new CreateCompanyHandler($this->repository))->handle(new CreateCompany($name))->value;
    }

    public function testUpdatesName(): void
    {
        $id = $this->create('Old Name');

        $this->handler->handle(new UpdateCompany($id, 'New Name'));

        $company = $this->repository->findById(new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id));
        $this->assertNotNull($company);
        $this->assertSame('New Name', $company->name->value);
    }

    public function testUpdatesOptionalFields(): void
    {
        $id = $this->create('Corp');

        $this->handler->handle(new UpdateCompany(
            id:    $id,
            name:  'Corp',
            email: 'new@corp.com',
            city:  'London',
        ));

        $company = $this->repository->findById(new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id));
        $this->assertNotNull($company);
        $this->assertSame('new@corp.com', $company->email);
        $this->assertSame('London',       $company->city);
    }

    public function testNullFieldsPreserveExistingValues(): void
    {
        $id = (new CreateCompanyHandler($this->repository))->handle(
            new CreateCompany('Corp', email: 'old@corp.com', city: 'Paris'),
        )->value;

        // Update with null email/city → should preserve old values
        $this->handler->handle(new UpdateCompany($id, 'Corp'));

        $company = $this->repository->findById(new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id));
        $this->assertNotNull($company);
        $this->assertSame('old@corp.com', $company->email);
        $this->assertSame('Paris',        $company->city);
    }

    public function testIsActiveCanBeToggled(): void
    {
        $id = $this->create('Corp');

        $this->handler->handle(new UpdateCompany($id, 'Corp', isActive: false));

        $company = $this->repository->findById(new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id));
        $this->assertNotNull($company);
        $this->assertFalse($company->isActive);
    }

    public function testThrowsWhenNotFound(): void
    {
        $this->expectException(CompanyNotFoundException::class);
        $this->handler->handle(new UpdateCompany('99999999-9999-4999-8999-999999999999', 'Name'));
    }

    public function testThrowsOnMalformedId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new UpdateCompany('not-a-uuid', 'Name'));
    }

    public function testThrowsOnDuplicateName(): void
    {
        $this->create('Alpha');
        $idBeta = $this->create('Beta');

        $this->expectException(CompanyAlreadyExistsException::class);
        $this->handler->handle(new UpdateCompany($idBeta, 'Alpha'));
    }

    public function testSameNameDoesNotThrowDuplicate(): void
    {
        $id = $this->create('Same');

        $this->handler->handle(new UpdateCompany($id, 'Same'));

        $company = $this->repository->findById(new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id));
        $this->assertSame('Same', $company?->name->value);
    }

    public function testPreservesCreatedAt(): void
    {
        $id      = $this->create('Corp');
        $before  = $this->repository->findById(
            new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id),
        );

        $this->handler->handle(new UpdateCompany($id, 'Corp Renamed'));

        $after = $this->repository->findById(new \Mossetc\TechnicalTest\Company\Domain\Model\CompanyId($id));
        $this->assertEquals($before?->createdAt, $after?->createdAt);
    }
}
