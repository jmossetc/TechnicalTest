<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Company\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Company\Application\Handler\ListCompaniesHandler;
use Mossetc\TechnicalTest\Company\Presentation\Controller\ListCompaniesController;

final class ListCompaniesControllerTest extends CompanyControllerTestCase
{
    private function ctrl(?Role $role = null): ListCompaniesController
    {
        return new ListCompaniesController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin),
            new ListCompaniesHandler($this->companyRepo),
        );
    }

    public function testReturns200WithDataAndPagination(): void
    {
        $this->seedCompany('Alpha');
        $this->seedCompany('Beta');

        $response = $this->ctrl()($this->authedRequest('GET', '/api/companies'));

        $this->assertSame(200, $response->status());
        $data = $response->data();
        $this->assertCount(2, $data['data']);
        $this->assertSame(2,  $data['pagination']['total']);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()($this->unauthRequest('GET', '/api/companies'));

        $this->assertSame(401, $response->status());
    }

    public function testReturns403WhenNotAdmin(): void
    {
        $response = $this->ctrl(Role::Employee)(
            $this->authedRequest('GET', '/api/companies'),
        );

        $this->assertSame(403, $response->status());
    }

    public function testResponseItemContainsExpectedFields(): void
    {
        $this->seedCompany('Test');

        $item = $this->ctrl()($this->authedRequest('GET', '/api/companies'))->data()['data'][0];

        $this->assertArrayHasKey('id',         $item);
        $this->assertArrayHasKey('name',       $item);
        $this->assertArrayHasKey('email',      $item);
        $this->assertArrayHasKey('city',       $item);
        $this->assertArrayHasKey('country',    $item);
        $this->assertArrayHasKey('is_active',  $item);
        $this->assertArrayHasKey('created_at', $item);
    }

    public function testPaginationQueryParamsAreRespected(): void
    {
        $this->seedCompany('A'); $this->seedCompany('B'); $this->seedCompany('C');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/companies', query: ['page' => '1', 'limit' => '2'],
        ));

        $this->assertCount(2, $response->data()['data']);
        $this->assertSame(3, $response->data()['pagination']['total']);
    }

    public function testNameFilterIsApplied(): void
    {
        $this->seedCompany('Alpha Corp'); $this->seedCompany('Beta Ltd');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/companies', query: ['name' => 'Alpha'],
        ));

        $this->assertCount(1, $response->data()['data']);
        $this->assertSame('Alpha Corp', $response->data()['data'][0]['name']);
    }

    public function testEmptyNameQueryParamIsIgnored(): void
    {
        $this->seedCompany('Any');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/companies', query: ['name' => ''],
        ));

        $this->assertCount(1, $response->data()['data']);
    }
}
