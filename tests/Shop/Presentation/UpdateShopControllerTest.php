<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Shop\Application\Handler\GetShopHandler;
use Mossetc\TechnicalTest\Shop\Application\Handler\UpdateShopHandler;
use Mossetc\TechnicalTest\Shop\Presentation\Controller\UpdateShopController;

final class UpdateShopControllerTest extends ShopControllerTestCase
{
    private const string COMPANY_A    = '11111111-1111-4111-8111-111111111111';
    private const string UNKNOWN_UUID = '550e8400-e29b-41d4-a716-446655440000';

    private function ctrl(
        ?Role $role = null,
        ?string $companyId = null,
        ?string $shopId = null,
    ): UpdateShopController {
        return new UpdateShopController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId, $shopId),
            new GetShopHandler($this->shopRepo),
            new UpdateShopHandler($this->shopRepo),
            $this->makeValidator(),
        );
    }

    public function testReturns200WithUpdatedTrueOnSuccess(): void
    {
        $id = $this->seedShop(self::COMPANY_A, 'Original');

        $response = $this->ctrl()(
            $this->authedRequest('PUT', "/api/shops/{$id->value}", ['name' => 'Updated'], ['id' => $id->value]),
        );

        self::assertSame(200, $response->status());
        self::assertTrue($response->data()['updated']);
    }

    public function testShopNameIsUpdatedInRepository(): void
    {
        $id = $this->seedShop(self::COMPANY_A, 'Original');
        $this->ctrl()(
            $this->authedRequest('PUT', "/api/shops/{$id->value}", ['name' => 'Updated'], ['id' => $id->value]),
        );

        $shop = $this->shopRepo->findById($id);
        self::assertNotNull($shop);
        self::assertSame('Updated', $shop->name->value);
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $id = $this->seedShop(self::COMPANY_A);
        $response = $this->ctrl()($this->unauthRequest('PUT', "/api/shops/{$id->value}"));
        self::assertSame(401, $response->status());
    }

    public function testReturns404WhenShopNotFound(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('PUT', '/api/shops/' . self::UNKNOWN_UUID, ['name' => 'X'], ['id' => self::UNKNOWN_UUID]),
        );
        self::assertSame(404, $response->status());
    }

    public function testReturns400ForMalformedId(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('PUT', '/api/shops/bad-id', ['name' => 'X'], ['id' => 'bad-id']),
        );
        self::assertSame(400, $response->status());
    }

    public function testReturns403WhenCallerLacksShopAccess(): void
    {
        $id = $this->seedShop(self::COMPANY_A);

        $response = $this->ctrl(Role::CompanyAdmin, '22222222-2222-4222-8222-222222222222')(
            $this->authedRequest('PUT', "/api/shops/{$id->value}", ['name' => 'X'], ['id' => $id->value]),
        );
        self::assertSame(403, $response->status());
    }

    public function testReturns422WhenNameIsEmpty(): void
    {
        $id = $this->seedShop(self::COMPANY_A, 'Original');

        $response = $this->ctrl()(
            $this->authedRequest('PUT', "/api/shops/{$id->value}", ['name' => ''], ['id' => $id->value]),
        );
        self::assertSame(422, $response->status());
    }

    public function testReturns422WhenNameIsMissing(): void
    {
        $id = $this->seedShop(self::COMPANY_A, 'Original');

        $response = $this->ctrl()(
            $this->authedRequest('PUT', "/api/shops/{$id->value}", [], ['id' => $id->value]),
        );
        self::assertSame(422, $response->status());
    }

    public function testReturns409WhenNewNameConflictsWithAnotherShop(): void
    {
        $id = $this->seedShop(self::COMPANY_A, 'Original');
        $this->seedShop(self::COMPANY_A, 'Taken');

        $response = $this->ctrl()(
            $this->authedRequest('PUT', "/api/shops/{$id->value}", ['name' => 'Taken'], ['id' => $id->value]),
        );
        self::assertSame(409, $response->status());
    }
}
