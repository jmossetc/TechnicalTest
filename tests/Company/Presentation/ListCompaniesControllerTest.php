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
        $items = $data['data'];
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $pagination = $data['pagination'];
        $this->assertIsArray($pagination);
        $this->assertSame(2, $pagination['total']);
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

        $items = $this->ctrl()($this->authedRequest('GET', '/api/companies'))->data()['data'];
        $this->assertIsArray($items);
        $item = $items[0];
        $this->assertIsArray($item);
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

        $data = $response->data();
        $items = $data['data'];
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $pagination = $data['pagination'];
        $this->assertIsArray($pagination);
        $this->assertSame(3, $pagination['total']);
    }

    public function testNameFilterIsApplied(): void
    {
        $this->seedCompany('Alpha Corp'); $this->seedCompany('Beta Ltd');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/companies', query: ['name' => 'Alpha'],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $first = $items[0];
        $this->assertIsArray($first);
        $this->assertSame('Alpha Corp', $first['name']);
    }

    public function testEmptyNameQueryParamIsIgnored(): void
    {
        $this->seedCompany('Any');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/companies', query: ['name' => ''],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }
}
