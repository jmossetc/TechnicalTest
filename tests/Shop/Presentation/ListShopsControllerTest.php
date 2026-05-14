<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Shop\Application\Handler\ListShopsHandler;
use Mossetc\TechnicalTest\Shop\Presentation\Controller\ListShopsController;

final class ListShopsControllerTest extends ShopControllerTestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string COMPANY_B = '22222222-2222-4222-8222-222222222222';
    private const string SHOP_A1   = 'aaaa1111-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private function ctrl(
        ?Role $role = null,
        ?string $companyId = null,
        ?string $shopId = null,
    ): ListShopsController {
        return new ListShopsController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId, $shopId),
            new ListShopsHandler($this->shopRepo),
        );
    }

    public function testReturns401WhenNoAuth(): void
    {
        $response = $this->ctrl()($this->unauthRequest('GET', '/api/shops'));

        $this->assertSame(401, $response->status());
    }

    public function testReturns403ForShopManager(): void
    {
        $response = $this->ctrl(Role::ShopManager, shopId: self::SHOP_A1)(
            $this->authedRequest('GET', '/api/shops'),
        );

        $this->assertSame(403, $response->status());
    }

    public function testReturns403ForEmployee(): void
    {
        $response = $this->ctrl(Role::Employee)(
            $this->authedRequest('GET', '/api/shops'),
        );

        $this->assertSame(403, $response->status());
    }

    public function testAdminSeesAllShops(): void
    {
        $this->seedShop(self::COMPANY_A, 'Alpha');
        $this->seedShop(self::COMPANY_B, 'Beta');

        $response = $this->ctrl()($this->authedRequest('GET', '/api/shops'));

        $data = $response->data();
        $this->assertSame(200, $response->status());
        $items = $data['data'];
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
    }

    public function testAdminCanFilterByCompanyId(): void
    {
        $this->seedShop(self::COMPANY_A, 'Alpha');
        $this->seedShop(self::COMPANY_B, 'Beta');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['company_id' => self::COMPANY_A],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Alpha', $items[0]['name']);
    }

    public function testCompanyAdminSeesOnlyOwnCompanyShops(): void
    {
        $this->seedShop(self::COMPANY_A, 'Own Shop');
        $this->seedShop(self::COMPANY_B, 'Other Shop');

        $response = $this->ctrl(Role::CompanyAdmin, self::COMPANY_A)(
            $this->authedRequest('GET', '/api/shops', query: ['company_id' => self::COMPANY_B]),
        );

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Own Shop', $items[0]['name']);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $this->seedShop(self::COMPANY_A, 'Test Shop');

        $response = $this->ctrl()($this->authedRequest('GET', '/api/shops'));

        $data = $response->data();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $pagination = $data['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('pages', $pagination);
    }

    public function testPaginationIsRespected(): void
    {
        $this->seedShop(self::COMPANY_A, 'A');
        $this->seedShop(self::COMPANY_A, 'B');
        $this->seedShop(self::COMPANY_A, 'C');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['page' => '1', 'limit' => '2'],
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
        $this->seedShop(self::COMPANY_A, 'Flagship Store');
        $this->seedShop(self::COMPANY_A, 'Corner Shop');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['name' => 'Flagship'],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Flagship Store', $items[0]['name']);
    }

    public function testCityFilterIsApplied(): void
    {
        $this->seedShop(self::COMPANY_A, 'Paris Shop',  city: 'Paris');
        $this->seedShop(self::COMPANY_A, 'London Shop', city: 'London');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['city' => 'Paris'],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Paris Shop', $items[0]['name']);
    }

    public function testIsDigitalTrueFilterIsApplied(): void
    {
        $this->seedShop(self::COMPANY_A, 'Physical', isDigital: false);
        $this->seedShop(self::COMPANY_A, 'Online',   isDigital: true);

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['is_digital' => 'true'],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Online', $items[0]['name']);
    }

    public function testIsDigitalFalseFilterIsApplied(): void
    {
        $this->seedShop(self::COMPANY_A, 'Physical', isDigital: false);
        $this->seedShop(self::COMPANY_A, 'Online',   isDigital: true);

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['is_digital' => 'false'],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame('Physical', $items[0]['name']);
    }

    public function testInvalidIsDigitalValueReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['is_digital' => 'maybe'],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testInvalidCreatedFromDateReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['created_from' => 'not-a-date'],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testInvalidCreatedToDateReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['created_to' => '32-13-2025'],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testCreatedFromAfterCreatedToReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: [
                'created_from' => '2025-12-31',
                'created_to'   => '2025-01-01',
            ],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testEmptyFilterParamsAreIgnored(): void
    {
        $this->seedShop(self::COMPANY_A, 'Any');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['name' => '', 'city' => '', 'is_digital' => ''],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }

    public function testDefaultSortAppliesNameAsc(): void
    {
        $this->seedShop(self::COMPANY_A, 'Gamma');
        $this->seedShop(self::COMPANY_A, 'Alpha');

        $response = $this->ctrl()($this->authedRequest('GET', '/api/shops'));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertSame('Alpha', $items[0]['name']);
    }

    public function testSortByNameDescendingReturnsReverseOrder(): void
    {
        $this->seedShop(self::COMPANY_A, 'Alpha');
        $this->seedShop(self::COMPANY_A, 'Gamma');

        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['sort_by' => 'name', 'sort_direction' => 'desc'],
        ));

        $items = $response->data()['data'];
        $this->assertIsArray($items);
        $this->assertSame(200, $response->status());
        $this->assertSame('Gamma', $items[0]['name']);
    }

    public function testInvalidSortByValueReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['sort_by' => 'invalid_column'],
        ));

        $this->assertSame(422, $response->status());
    }

    public function testInvalidSortDirectionValueReturns422(): void
    {
        $response = $this->ctrl()($this->authedRequest(
            'GET', '/api/shops', query: ['sort_direction' => 'sideways'],
        ));

        $this->assertSame(422, $response->status());
    }
}
