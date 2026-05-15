<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Company\Application\Handler\DeleteCompanyHandler;
use Mossetc\TechnicalTest\Company\Presentation\Controller\DeleteCompanyController;

final class DeleteCompanyControllerTest extends CompanyControllerTestCase
{
    private function ctrl(?Role $role = null): DeleteCompanyController
    {
        return new DeleteCompanyController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin),
            new DeleteCompanyHandler($this->companyRepo),
        );
    }

    public function testReturns200AndDeletesCompanyOnSuccess(): void
    {
        $id       = $this->seedCompany('To Delete');
        $response = $this->ctrl()($this->authedRequest(
            'DELETE',
            "/api/companies/{$id->value}",
            attrs: ['id' => $id->value],
        ));

        self::assertSame(200, $response->status());
        self::assertTrue($response->data()['deleted'] ?? false);
        self::assertNull($this->companyRepo->findById($id));
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $id       = $this->seedCompany();
        $response = $this->ctrl()($this->unauthRequest('DELETE', "/api/companies/{$id->value}", ['id' => $id->value]));

        self::assertSame(401, $response->status());
    }

    public function testReturns403WhenNotAdmin(): void
    {
        $id       = $this->seedCompany();
        $response = $this->ctrl(Role::Employee)($this->authedRequest(
            'DELETE',
            "/api/companies/{$id->value}",
            attrs: ['id' => $id->value],
        ));

        self::assertSame(403, $response->status());
    }

    public function testReturns404WhenNotFound(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'DELETE',
            '/api/companies/99999999-9999-4999-8999-999999999999',
            attrs: ['id' => '99999999-9999-4999-8999-999999999999'],
        ));

        self::assertSame(404, $response->status());
    }

    public function testReturns422ForMalformedId(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'DELETE',
            '/api/companies/bad',
            attrs: ['id' => 'bad'],
        ));

        self::assertSame(422, $response->status());
    }
}
