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

        self::assertSame(200, $response->status());
        $data = $response->data();
        $items = $data['data'];
        self::assertIsArray($items);
        self::assertCount(2, $items);
        $pagination = $data['pagination'];
        self::assertIsArray($pagination);
        self::assertSame(2, $pagination['total']);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()($this->unauthRequest('GET', '/api/companies'));

        self::assertSame(401, $response->status());
    }

    public function testReturns403WhenNotAdmin(): void
    {
        $response = $this->ctrl(Role::Employee)(
            $this->authedRequest('GET', '/api/companies'),
        );

        self::assertSame(403, $response->status());
    }

    public function testResponseItemContainsExpectedFields(): void
    {
        $this->seedCompany('Test');

        $items = $this->ctrl()($this->authedRequest('GET', '/api/companies'))->data()['data'];
        self::assertIsArray($items);
        $item = $items[0];
        self::assertIsArray($item);
        self::assertArrayHasKey('id', $item);
        self::assertArrayHasKey('name', $item);
        self::assertArrayHasKey('email', $item);
        self::assertArrayHasKey('city', $item);
        self::assertArrayHasKey('country', $item);
        self::assertArrayHasKey('is_active', $item);
        self::assertArrayHasKey('created_at', $item);
    }

    public function testPaginationQueryParamsAreRespected(): void
    {
        $this->seedCompany('A');
        $this->seedCompany('B');
        $this->seedCompany('C');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['page' => '1', 'limit' => '2'],
        ));

        $data = $response->data();
        $items = $data['data'];
        self::assertIsArray($items);
        self::assertCount(2, $items);
        $pagination = $data['pagination'];
        self::assertIsArray($pagination);
        self::assertSame(3, $pagination['total']);
    }

    public function testNameFilterIsApplied(): void
    {
        $this->seedCompany('Alpha Corp');
        $this->seedCompany('Beta Ltd');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['name' => 'Alpha'],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
        $first = $items[0];
        self::assertIsArray($first);
        self::assertSame('Alpha Corp', $first['name']);
    }

    public function testEmptyNameQueryParamIsIgnored(): void
    {
        $this->seedCompany('Any');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['name' => ''],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
    }

    public function testEmailFilterIsApplied(): void
    {
        $this->seedCompanyFull('Alpha', email: 'alpha@example.com');
        $this->seedCompanyFull('Beta', email: 'beta@example.com');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['email' => 'alpha'],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
        $first = $items[0];
        self::assertIsArray($first);
        self::assertSame('Alpha', $first['name']);
    }

    public function testCityFilterIsApplied(): void
    {
        $this->seedCompanyFull('Alpha', city: 'Paris');
        $this->seedCompanyFull('Beta', city: 'London');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['city' => 'Paris'],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Alpha', $first['name']);
    }

    public function testCountryFilterIsApplied(): void
    {
        $this->seedCompanyFull('Alpha', country: 'France');
        $this->seedCompanyFull('Beta', country: 'UK');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['country' => 'France'],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Alpha', $first['name']);
    }

    public function testPostalCodeFilterIsApplied(): void
    {
        $this->seedCompanyFull('Alpha', postalCode: '75001');
        $this->seedCompanyFull('Beta', postalCode: '69001');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['postal_code' => '750'],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Alpha', $first['name']);
    }

    public function testPhoneNumberFilterIsApplied(): void
    {
        $this->seedCompanyFull('Alpha', phoneNumber: '+33123456789');
        $this->seedCompanyFull('Beta', phoneNumber: '+44207946');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['phone_number' => '+33'],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Alpha', $first['name']);
    }

    public function testInvalidCreatedFromDateReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['created_from' => 'not-a-date'],
        ));

        self::assertSame(422, $response->status());
    }

    public function testInvalidCreatedToDateReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['created_to' => '32-13-2025'],
        ));

        self::assertSame(422, $response->status());
    }

    public function testCreatedFromAfterCreatedToReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: [
                'created_from' => '2025-12-31',
                'created_to'   => '2025-01-01',
            ],
        ));

        self::assertSame(422, $response->status());
    }

    public function testEmptyFilterParamsAreIgnored(): void
    {
        $this->seedCompany('Any');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['email' => '', 'city' => '', 'country' => ''],
        ));

        $items = $response->data()['data'];
        self::assertIsArray($items);
        self::assertCount(1, $items);
    }

    public function testDefaultSortIsNameAsc(): void
    {
        $this->seedCompany('Beta');
        $this->seedCompany('Alpha');

        $items = $this->ctrl()($this->authedRequest('GET', '/api/companies'))->data()['data'];
        self::assertIsArray($items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Alpha', $first['name']);
    }

    public function testSortByNameDescReturnsReverseOrder(): void
    {
        $this->seedCompany('Alpha');
        $this->seedCompany('Beta');

        $items = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['sort_by' => 'name', 'sort_direction' => 'desc'],
        ))->data()['data'];
        self::assertIsArray($items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Beta', $first['name']);
    }

    public function testSortByCreatedAtAscIsAccepted(): void
    {
        $this->seedCompany('Any');

        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['sort_by' => 'created_at'],
        ));
        self::assertSame(200, $response->status());
    }

    public function testInvalidSortByReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['sort_by' => 'invalid_column'],
        ));
        self::assertSame(422, $response->status());
    }

    public function testInvalidSortDirectionReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['sort_direction' => 'sideways'],
        ));
        self::assertSame(422, $response->status());
    }

    public function testEmptySortParamsUseDefaults(): void
    {
        $this->seedCompany('Beta');
        $this->seedCompany('Alpha');

        $items = $this->ctrl()($this->authedRequest(
            'GET',
            '/api/companies',
            query: ['sort_by' => '', 'sort_direction' => ''],
        ))->data()['data'];
        self::assertIsArray($items);
        $first = $items[0];
        if (!\is_array($first)) {
            self::fail('Expected array element');
        }
        self::assertSame('Alpha', $first['name']);
    }
}
