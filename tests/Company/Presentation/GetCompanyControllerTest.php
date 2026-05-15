<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Company\Application\Handler\GetCompanyHandler;
use Mossetc\TechnicalTest\Company\Presentation\Controller\GetCompanyController;

final class GetCompanyControllerTest extends CompanyControllerTestCase
{
    private function ctrl(?Role $role = null, ?string $companyId = null): GetCompanyController
    {
        return new GetCompanyController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId),
            new GetCompanyHandler($this->companyRepo),
        );
    }

    public function testReturns200WithAllFieldsOnSuccess(): void
    {
        $id       = $this->seedCompany('Acme');
        $response = $this->ctrl()($this->authedRequest('GET', "/api/companies/{$id->value}", attrs: ['id' => $id->value]));

        self::assertSame(200, $response->status());
        $data = $response->data();
        self::assertSame($id->value, $data['id']);
        self::assertSame('Acme', $data['name']);
        self::assertArrayHasKey('email', $data);
        self::assertArrayHasKey('phone_number', $data);
        self::assertArrayHasKey('website', $data);
        self::assertArrayHasKey('address_line_1', $data);
        self::assertArrayHasKey('city', $data);
        self::assertArrayHasKey('is_active', $data);
        self::assertArrayHasKey('created_at', $data);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $id       = $this->seedCompany();
        $response = $this->ctrl()($this->unauthRequest('GET', "/api/companies/{$id->value}", ['id' => $id->value]));

        self::assertSame(401, $response->status());
    }

    public function testReturns403WhenForbidden(): void
    {
        $id = $this->seedCompany();
        // company admin of a different company → forbidden
        $response = $this->ctrl(Role::CompanyAdmin, '22222222-2222-4222-8222-222222222222')(
            $this->authedRequest('GET', "/api/companies/{$id->value}", attrs: ['id' => $id->value]),
        );

        self::assertSame(403, $response->status());
    }

    public function testReturns404WhenNotFound(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies/99999999-9999-4999-8999-999999999999',
            attrs: ['id' => '99999999-9999-4999-8999-999999999999'],
        ));

        self::assertSame(404, $response->status());
    }

    public function testReturns400ForMalformedId(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies/bad-id',
            attrs: ['id' => 'bad-id'],
        ));

        self::assertSame(400, $response->status());
    }

    public function testCompanyAdminCanAccessOwnCompany(): void
    {
        $id       = $this->seedCompany('Mine');
        $response = $this->ctrl(Role::CompanyAdmin, $id->value)(
            $this->authedRequest('GET', "/api/companies/{$id->value}", attrs: ['id' => $id->value]),
        );

        self::assertSame(200, $response->status());
    }
}
