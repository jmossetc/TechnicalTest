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
        $this->assertSame('Acme', $cmd->name);
    }

    public function testCreateCompanyStoresOptionalFields(): void
    {
        $cmd = new CreateCompany(
            name:         'Full Corp',
            email:        'info@full.com',
            phoneNumber:  '+33123456789',
            website:      'https://full.com',
            addressLine1: '10 Rue de la Paix',
            addressLine2: 'Bâtiment B',
            city:         'Paris',
            postalCode:   '75001',
            country:      'France',
        );
        $this->assertSame('info@full.com',     $cmd->email);
        $this->assertSame('+33123456789',       $cmd->phoneNumber);
        $this->assertSame('https://full.com',  $cmd->website);
        $this->assertSame('10 Rue de la Paix', $cmd->addressLine1);
        $this->assertSame('Bâtiment B',        $cmd->addressLine2);
        $this->assertSame('Paris',             $cmd->city);
        $this->assertSame('75001',             $cmd->postalCode);
        $this->assertSame('France',            $cmd->country);
    }

    public function testCreateCompanyOptionalFieldsDefaultToNull(): void
    {
        $cmd = new CreateCompany('Acme');
        $this->assertNull($cmd->email);
        $this->assertNull($cmd->phoneNumber);
        $this->assertNull($cmd->website);
        $this->assertNull($cmd->addressLine1);
        $this->assertNull($cmd->city);
        $this->assertNull($cmd->country);
    }

    public function testUpdateCompanyStoresIdAndName(): void
    {
        $cmd = new UpdateCompany('some-id', 'New Name');
        $this->assertSame('some-id',  $cmd->id);
        $this->assertSame('New Name', $cmd->name);
    }

    public function testUpdateCompanyOptionalFieldsDefaultToNull(): void
    {
        $cmd = new UpdateCompany('some-id', 'Name');
        $this->assertNull($cmd->email);
        $this->assertNull($cmd->isActive);
    }
}
