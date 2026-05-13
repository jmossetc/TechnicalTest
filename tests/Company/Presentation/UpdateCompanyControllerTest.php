<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Company\Application\Handler\UpdateCompanyHandler;
use Mossetc\TechnicalTest\Company\Presentation\Controller\UpdateCompanyController;

final class UpdateCompanyControllerTest extends CompanyControllerTestCase
{
    private function ctrl(?Role $role = null, ?string $companyId = null): UpdateCompanyController
    {
        return new UpdateCompanyController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId),
            new UpdateCompanyHandler($this->companyRepo),
        );
    }

    public function testReturns200OnSuccess(): void
    {
        $id       = $this->seedCompany('Old Name');
        $response = $this->ctrl()($this->authedRequest(
            'PATCH', "/api/companies/{$id->value}",
            body:  ['name' => 'New Name'],
            attrs: ['id'   => $id->value],
        ));

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->data()['updated'] ?? false);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $id       = $this->seedCompany();
        $response = $this->ctrl()($this->unauthRequest('PATCH', "/api/companies/{$id->value}", ['id' => $id->value]));

        $this->assertSame(401, $response->status());
    }

    public function testReturns403WhenForbidden(): void
    {
        $id       = $this->seedCompany();
        $response = $this->ctrl(Role::Employee)($this->authedRequest(
            'PATCH', "/api/companies/{$id->value}",
            body:  ['name' => 'X'],
            attrs: ['id'   => $id->value],
        ));

        $this->assertSame(403, $response->status());
    }

    public function testReturns422WhenNameIsEmpty(): void
    {
        $id       = $this->seedCompany();
        $response = $this->ctrl()($this->authedRequest(
            'PATCH', "/api/companies/{$id->value}",
            body:  ['name' => ''],
            attrs: ['id'   => $id->value],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testReturns404WhenNotFound(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'PATCH', '/api/companies/99999999-9999-4999-8999-999999999999',
            body:  ['name' => 'X'],
            attrs: ['id'   => '99999999-9999-4999-8999-999999999999'],
        ));

        $this->assertSame(404, $response->status());
    }

    public function testReturns409OnDuplicateName(): void
    {
        $this->seedCompany('Alpha');
        $idBeta = $this->seedCompany('Beta');

        $response = $this->ctrl()($this->authedRequest(
            'PATCH', "/api/companies/{$idBeta->value}",
            body:  ['name' => 'Alpha'],
            attrs: ['id'   => $idBeta->value],
        ));

        $this->assertSame(409, $response->status());
    }

    public function testIsActiveIsParsedAsBooleanTrue(): void
    {
        $id = $this->seedCompany();

        $this->ctrl()($this->authedRequest(
            'PATCH', "/api/companies/{$id->value}",
            body:  ['name' => 'Corp', 'is_active' => true],
            attrs: ['id'   => $id->value],
        ));

        $this->assertTrue($this->companyRepo->findById($id)?->isActive);
    }

    public function testIsActiveIsParsedAsBooleanFalse(): void
    {
        $id = $this->seedCompany();

        $this->ctrl()($this->authedRequest(
            'PATCH', "/api/companies/{$id->value}",
            body:  ['name' => 'Corp', 'is_active' => false],
            attrs: ['id'   => $id->value],
        ));

        $this->assertFalse($this->companyRepo->findById($id)?->isActive);
    }

    public function testCompanyAdminCanUpdateOwnCompany(): void
    {
        $id       = $this->seedCompany('Mine');
        $response = $this->ctrl(Role::CompanyAdmin, $id->value)($this->authedRequest(
            'PATCH', "/api/companies/{$id->value}",
            body:  ['name' => 'Mine Updated'],
            attrs: ['id'   => $id->value],
        ));

        $this->assertSame(200, $response->status());
    }
}
