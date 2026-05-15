<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyAlreadyExistsException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use Mossetc\TechnicalTest\Tests\Support\InMemoryCompanyRepository;
use PHPUnit\Framework\TestCase;

final class CreateCompanyHandlerTest extends TestCase
{
    private InMemoryCompanyRepository $repository;
    private CreateCompanyHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCompanyRepository();
        $this->handler    = new CreateCompanyHandler($this->repository);
    }

    public function testCreatesCompanyAndReturnsId(): void
    {
        $id = $this->handler->handle(new CreateCompany('Acme Corp'));

        $saved = $this->repository->findById($id);
        self::assertNotNull($saved);
        self::assertSame('Acme Corp', $saved->name->value);
    }

    public function testReturnedIdMatchesSavedCompany(): void
    {
        $id      = $this->handler->handle(new CreateCompany('Globex'));
        $company = $this->repository->findById($id);

        self::assertNotNull($company);
        self::assertTrue($id->equals($company->id));
    }

    public function testStoresAllOptionalFields(): void
    {
        $this->handler->handle(new CreateCompany(
            name: 'Full Corp',
            email: 'info@full.com',
            phoneNumber: '+33123456789',
            website: 'https://full.com',
            addressLine1: '10 Rue de la Paix',
            addressLine2: 'Bâtiment B',
            city: 'Paris',
            postalCode: '75001',
            country: 'France',
        ));

        $company = $this->repository->findByName(new CompanyName('Full Corp'));
        self::assertNotNull($company);
        self::assertSame('info@full.com', $company->email);
        self::assertSame('+33123456789', $company->phoneNumber);
        self::assertSame('https://full.com', $company->website);
        self::assertSame('10 Rue de la Paix', $company->addressLine1);
        self::assertSame('Bâtiment B', $company->addressLine2);
        self::assertSame('Paris', $company->city);
        self::assertSame('75001', $company->postalCode);
        self::assertSame('France', $company->country);
    }

    public function testThrowsWhenNameAlreadyExists(): void
    {
        $this->handler->handle(new CreateCompany('Acme Corp'));

        $this->expectException(CompanyAlreadyExistsException::class);
        $this->handler->handle(new CreateCompany('Acme Corp'));
    }

    public function testThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new CreateCompany(''));
    }

    public function testThrowsOnWhitespaceOnlyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle(new CreateCompany('   '));
    }
}
