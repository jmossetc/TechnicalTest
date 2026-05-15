<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Application\Handler\DeleteCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Tests\Support\InMemoryCompanyRepository;
use PHPUnit\Framework\TestCase;

final class DeleteCompanyHandlerTest extends TestCase
{
    private InMemoryCompanyRepository $repository;
    private DeleteCompanyHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCompanyRepository();
        $this->handler    = new DeleteCompanyHandler($this->repository);
    }

    public function testDeletesExistingCompany(): void
    {
        $id = new CreateCompanyHandler($this->repository)->handle(new CreateCompany('Acme'));

        $this->handler->handle($id->value);

        self::assertNull($this->repository->findById($id));
    }

    public function testThrowsWhenNotFound(): void
    {
        $this->expectException(CompanyNotFoundException::class);
        $this->handler->handle('99999999-9999-4999-8999-999999999999');
    }

    public function testThrowsOnMalformedId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->handler->handle('not-a-uuid');
    }
}
