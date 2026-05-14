<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Company\Application\Handler\CreateCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
use Mossetc\TechnicalTest\Company\Presentation\Controller\CreateCompanyController;

final class CreateCompanyControllerTest extends CompanyControllerTestCase
{
    private function ctrl(?Role $callerRole = null): CreateCompanyController
    {
        return new CreateCompanyController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($callerRole ?? Role::Admin),
            new CreateCompanyHandler($this->companyRepo),
            $this->makeValidator(),
        );
    }

    public function testReturns201WithIdOnSuccess(): void
    {
        $response = $this->ctrl()($this->authedRequest('POST', '/api/companies', ['name' => 'Acme']));

        $this->assertSame(201, $response->status());
        $this->assertArrayHasKey('id', $response->data());
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()($this->unauthRequest('POST', '/api/companies'));

        $this->assertSame(401, $response->status());
    }

    public function testReturns403WhenNotAdmin(): void
    {
        $response = $this->ctrl(Role::Employee)(
            $this->authedRequest('POST', '/api/companies', ['name' => 'Corp']),
        );

        $this->assertSame(403, $response->status());
    }

    public function testReturns422WhenNameIsEmpty(): void
    {
        $response = $this->ctrl()($this->authedRequest('POST', '/api/companies', ['name' => '']));

        $this->assertSame(422, $response->status());
    }

    public function testReturns422WhenNameIsMissing(): void
    {
        $response = $this->ctrl()($this->authedRequest('POST', '/api/companies', []));

        $this->assertSame(422, $response->status());
    }

    public function testReturns409OnDuplicateName(): void
    {
        $this->seedCompany('Taken');

        $response = $this->ctrl()($this->authedRequest('POST', '/api/companies', ['name' => 'Taken']));

        $this->assertSame(409, $response->status());
    }

    public function testStoresCompanyInRepository(): void
    {
        $this->ctrl()($this->authedRequest('POST', '/api/companies', ['name' => 'New Corp']));

        $this->assertSame(1, $this->companyRepo->countByCriteria(new CompanySearchCriteria()));
    }
}
