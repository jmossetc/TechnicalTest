<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Shop\Application\Handler\DeleteShopHandler;
use Mossetc\TechnicalTest\Shop\Application\Handler\GetShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Presentation\Controller\DeleteShopController;

final class DeleteShopControllerTest extends ShopControllerTestCase
{
    private const string COMPANY_A    = '11111111-1111-4111-8111-111111111111';
    private const string UNKNOWN_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private function ctrl(?Role $role = null, ?string $companyId = null): DeleteShopController
    {
        return new DeleteShopController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId),
            new GetShopHandler($this->shopRepo),
            new DeleteShopHandler($this->shopRepo),
        );
    }

    public function testReturns200WithDeletedTrueOnSuccess(): void
    {
        $id = $this->seedShop(self::COMPANY_A);

        $response = $this->ctrl()(
            $this->authedRequest('DELETE', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );

        self::assertSame(200, $response->status());
        self::assertTrue($response->data()['deleted']);
    }

    public function testShopIsRemovedFromRepositoryAfterDelete(): void
    {
        $id = $this->seedShop(self::COMPANY_A);
        $this->ctrl()(
            $this->authedRequest('DELETE', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );

        self::assertSame(0, $this->shopRepo->countByCriteria(new ShopSearchCriteria()));
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $id = $this->seedShop(self::COMPANY_A);
        $response = $this->ctrl()($this->unauthRequest('DELETE', "/api/shops/{$id->value}"));
        self::assertSame(401, $response->status());
    }

    public function testReturns404WhenShopNotFound(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('DELETE', '/api/shops/' . self::UNKNOWN_UUID, attrs: ['id' => self::UNKNOWN_UUID]),
        );
        self::assertSame(404, $response->status());
    }

    public function testReturns400ForMalformedId(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('DELETE', '/api/shops/bad-id', attrs: ['id' => 'bad-id']),
        );
        self::assertSame(400, $response->status());
    }

    public function testReturns403WhenCallerLacksCompanyAccess(): void
    {
        $id = $this->seedShop(self::COMPANY_A);

        $response = $this->ctrl(Role::CompanyAdmin, '22222222-2222-4222-8222-222222222222')(
            $this->authedRequest('DELETE', "/api/shops/{$id->value}", attrs: ['id' => $id->value]),
        );
        self::assertSame(403, $response->status());
    }
}
