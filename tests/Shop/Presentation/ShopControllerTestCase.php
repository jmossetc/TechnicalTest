<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Tests\Shop\Presentation;

use Mossetc\TechnicalTest\Auth\Domain\Model\Email;
use Mossetc\TechnicalTest\Auth\Domain\Model\FirstName;
use Mossetc\TechnicalTest\Auth\Domain\Model\HashedPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\LastName;
use Mossetc\TechnicalTest\Auth\Domain\Model\PlainPassword;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\TokenServiceInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Service\ShopInputValidatorService;
use Mossetc\TechnicalTest\Tests\Support\InMemoryShopRepository;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;

abstract class ShopControllerTestCase extends TestCase
{
    protected UserId $callerId;
    protected InMemoryShopRepository $shopRepo;

    protected function setUp(): void
    {
        $this->callerId = UserId::generate();
        $this->shopRepo = new InMemoryShopRepository();
    }

    protected function makeValidator(): ShopInputValidatorService
    {
        return new ShopInputValidatorService(PhoneNumberUtil::getInstance());
    }

    protected function makeAuth(): JwtAuthMiddleware
    {
        $svc = $this->createStub(TokenServiceInterface::class);
        $svc->method('validate')->willReturn($this->callerId);

        return new JwtAuthMiddleware($svc);
    }

    protected function makeAuthzWithRole(
        Role $role,
        ?string $companyId = null,
        ?string $shopId = null,
    ): UserAuthorizationService {
        $caller = new User(
            id:        $this->callerId,
            email:     new Email('caller@test.test'),
            password:  HashedPassword::fromPlain(new PlainPassword('password123')),
            firstName: new FirstName('Caller'),
            lastName:  new LastName('User'),
            role:      $role,
            companyId: $companyId,
            shopId:    $shopId,
        );

        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($caller);

        return new UserAuthorizationService($repo);
    }

    protected function seedShop(
        string $companyId,
        string $name = 'Test Shop',
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $city = null,
        ?string $postalCode = null,
        ?string $country = null,
        bool $isDigital = false,
    ): ShopId {
        return (new CreateShopHandler($this->shopRepo))->handle(new CreateShop(
            companyId:   $companyId,
            name:        $name,
            email:       $email,
            phoneNumber: $phoneNumber,
            city:        $city,
            postalCode:  $postalCode,
            country:     $country,
            isDigital:   $isDigital,
        ));
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $attrs
     * @param array<string, string> $query
     */
    protected function authedRequest(string $method, string $path, array $body = [], array $attrs = [], array $query = []): Request
    {
        return new Request($method, $path, ['Authorization' => 'Bearer tok'], $body, $attrs, $query);
    }

    protected function unauthRequest(string $method, string $path): Request
    {
        return new Request($method, $path, [], [], []);
    }
}
