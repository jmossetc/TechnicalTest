<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Shop\Application\Handler\GetShopHandler;
use Mossetc\TechnicalTest\Shop\Presentation\Controller\GetShopController;

final class GetShopControllerTest extends ShopControllerTestCase
{
    private const string COMPANY_A    = '11111111-1111-4111-8111-111111111111';
    private const string UNKNOWN_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private function ctrl(
        ?Role $role = null,
        ?string $companyId = null,
        ?string $shopId = null,
    ): GetShopController {
        return new GetShopController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId, $shopId),
            new GetShopHandler($this->shopRepo),
        );
    }

    public function testReturns200WithShopDataOnSuccess(): void
    {
        $id = $this->seedShop(self::COMPANY_A, 'My Shop');

        $response = $this->ctrl()(
            $this->authedRequest('GET', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );

        self::assertSame(200, $response->status());
        $data = $response->data();
        self::assertSame($id->value, $data['id']);
        self::assertSame(self::COMPANY_A, $data['company_id']);
        self::assertSame('My Shop', $data['name']);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $id = $this->seedShop(self::COMPANY_A);
        $response = $this->ctrl()($this->unauthRequest('GET', "/api/shops/{$id->value}"));
        self::assertSame(401, $response->status());
    }

    public function testReturns404WhenShopNotFound(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('GET', '/api/shops/' . self::UNKNOWN_UUID, attrs: ['id' => self::UNKNOWN_UUID]),
        );
        self::assertSame(404, $response->status());
    }

    public function testReturns400ForMalformedId(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('GET', '/api/shops/bad-id', attrs: ['id' => 'bad-id']),
        );
        self::assertSame(400, $response->status());
    }

    public function testReturns403WhenCallerLacksShopAccess(): void
    {
        $id = $this->seedShop(self::COMPANY_A);

        $response = $this->ctrl(Role::CompanyAdmin, '22222222-2222-4222-8222-222222222222')(
            $this->authedRequest('GET', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );
        self::assertSame(403, $response->status());
    }

    public function testCompanyAdminCanAccessShopInOwnCompany(): void
    {
        $id = $this->seedShop(self::COMPANY_A);

        $response = $this->ctrl(Role::CompanyAdmin, self::COMPANY_A)(
            $this->authedRequest('GET', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );
        self::assertSame(200, $response->status());
    }

    public function testShopManagerCanAccessOwnShop(): void
    {
        $id = $this->seedShop(self::COMPANY_A);

        $response = $this->ctrl(Role::ShopManager, shopId: $id->value)(
            $this->authedRequest('GET', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );
        self::assertSame(200, $response->status());
    }
}
