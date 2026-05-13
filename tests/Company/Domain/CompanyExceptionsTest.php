<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Domain;

use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyAlreadyExistsException;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanyName;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CompanyExceptionsTest extends TestCase
{
    public function testAlreadyExistsMessageContainsName(): void
    {
        $e = new CompanyAlreadyExistsException(new CompanyName('Acme Corp'));

        $this->assertStringContainsString('Acme Corp', $e->getMessage());
        $this->assertInstanceOf(RuntimeException::class, $e);
    }

    public function testNotFoundMessageContainsId(): void
    {
        $id = '11111111-1111-4111-8111-111111111111';
        $e  = new CompanyNotFoundException($id);

        $this->assertStringContainsString($id, $e->getMessage());
        $this->assertInstanceOf(RuntimeException::class, $e);
    }
}
