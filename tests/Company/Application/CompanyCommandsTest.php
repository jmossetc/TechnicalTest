<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Application;

use Mossetc\TechnicalTest\Company\Application\Command\CreateCompany;
use Mossetc\TechnicalTest\Company\Application\Command\UpdateCompany;
use PHPUnit\Framework\TestCase;

final class CompanyCommandsTest extends TestCase
{
    public function testCreateCompanyStoresName(): void
    {
        $cmd = new CreateCompany('Acme');
        self::assertSame('Acme', $cmd->name);
    }

    public function testCreateCompanyStoresOptionalFields(): void
    {
        $cmd = new CreateCompany(
            name: 'Full Corp',
            email: 'info@full.com',
            phoneNumber: '+33123456789',
            website: 'https://full.com',
            addressLine1: '10 Rue de la Paix',
            addressLine2: 'Bâtiment B',
            city: 'Paris',
            postalCode: '75001',
            country: 'France',
        );
        self::assertSame('info@full.com', $cmd->email);
        self::assertSame('+33123456789', $cmd->phoneNumber);
        self::assertSame('https://full.com', $cmd->website);
        self::assertSame('10 Rue de la Paix', $cmd->addressLine1);
        self::assertSame('Bâtiment B', $cmd->addressLine2);
        self::assertSame('Paris', $cmd->city);
        self::assertSame('75001', $cmd->postalCode);
        self::assertSame('France', $cmd->country);
    }

    public function testCreateCompanyOptionalFieldsDefaultToNull(): void
    {
        $cmd = new CreateCompany('Acme');
        self::assertNull($cmd->email);
        self::assertNull($cmd->phoneNumber);
        self::assertNull($cmd->website);
        self::assertNull($cmd->addressLine1);
        self::assertNull($cmd->city);
        self::assertNull($cmd->country);
    }

    public function testUpdateCompanyStoresIdAndName(): void
    {
        $cmd = new UpdateCompany('some-id', 'New Name');
        self::assertSame('some-id', $cmd->id);
        self::assertSame('New Name', $cmd->name);
    }

    public function testUpdateCompanyOptionalFieldsDefaultToNull(): void
    {
        $cmd = new UpdateCompany('some-id', 'Name');
        self::assertNull($cmd->email);
        self::assertNull($cmd->isActive);
    }
}
