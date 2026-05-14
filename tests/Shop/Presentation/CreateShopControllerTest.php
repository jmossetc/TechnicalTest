<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Presentation\Controller\CreateShopController;

final class CreateShopControllerTest extends ShopControllerTestCase
{
    private const string COMPANY_A = '11111111-1111-4111-8111-111111111111';
    private const string COMPANY_B = '22222222-2222-4222-8222-222222222222';

    private function ctrl(?Role $role = null, ?string $companyId = null): CreateShopController
    {
        return new CreateShopController(
            $this->makeAuth(),
            $this->makeAuthzWithRole($role ?? Role::Admin, $companyId),
            new CreateShopHandler($this->shopRepo),
            $this->makeValidator(),
        );
    }

    public function testReturns201WithIdOnSuccess(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('POST', '/api/shops', ['name' => 'My Shop'], ['companyId' => self::COMPANY_A]),
        );

        $this->assertSame(201, $response->status());
        $this->assertArrayHasKey('id', $response->data());
    }

    public function testReturns401WhenNoAuthorizationHeader(): void
    {
        $response = $this->ctrl()($this->unauthRequest('POST', '/api/shops'));
        $this->assertSame(401, $response->status());
    }

    public function testReturns403WhenCallerLacksCompanyAccess(): void
    {
        $response = $this->ctrl(Role::CompanyAdmin, self::COMPANY_B)(
            $this->authedRequest('POST', '/api/shops', ['name' => 'X'], ['companyId' => self::COMPANY_A]),
        );
        $this->assertSame(403, $response->status());
    }

    public function testReturns422WhenNameIsEmpty(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('POST', '/api/shops', ['name' => ''], ['companyId' => self::COMPANY_A]),
        );
        $this->assertSame(422, $response->status());
    }

    public function testReturns422WhenNameIsMissing(): void
    {
        $response = $this->ctrl()(
            $this->authedRequest('POST', '/api/shops', [], ['companyId' => self::COMPANY_A]),
        );
        $this->assertSame(422, $response->status());
    }

    public function testReturns409OnDuplicateName(): void
    {
        $this->seedShop(self::COMPANY_A, 'Taken');

        $response = $this->ctrl()(
            $this->authedRequest('POST', '/api/shops', ['name' => 'Taken'], ['companyId' => self::COMPANY_A]),
        );
        $this->assertSame(409, $response->status());
    }

    public function testShopIsPersistedAfterSuccess(): void
    {
        $this->ctrl()(
            $this->authedRequest('POST', '/api/shops', ['name' => 'New Shop'], ['companyId' => self::COMPANY_A]),
        );

        $this->assertSame(1, $this->shopRepo->countByCriteria(new ShopSearchCriteria()));
    }
}
