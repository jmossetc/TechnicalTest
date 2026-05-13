<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopId;
use Mossetc\TechnicalTest\Shop\Domain\Repository\ShopRepositoryInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'register')]
final readonly class RegisterUserController implements ControllerInterface
{
    public function __construct(
        private RegisterUserHandler      $handler,
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorizationService,
        private ShopRepositoryInterface  $shopRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $callerId = $this->auth->authenticate($request->headers);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 401);
        } catch (InvalidTokenException) {
            return Response::error('Unauthorized', 401);
        }

        $email       = $request->stringBody('email');
        $password    = $request->stringBody('password');
        $firstName   = $request->stringBody('first_name');
        $lastName    = $request->stringBody('last_name');

        if ($email === '' || $password === '' || $firstName === '' || $lastName === '') {
            return Response::error('email, password, first_name and last_name are required', 422);
        }

        $roleStr     = $request->stringBody('role') ?: 'employee';
        $companyId   = $request->stringBody('company_id') ?: null;
        $shopId      = $request->stringBody('shop_id') ?: null;
        $phoneNumber = $request->stringBody('phone_number') ?: null;

        $targetRole = Role::tryFrom($roleStr);
        if ($targetRole === null) {
            return Response::error("Invalid role: {$roleStr}", 422);
        }

        if ($targetRole === Role::CompanyAdmin && $companyId === null) {
            return Response::error('company_id is required for company_admin role', 422);
        }

        if ($targetRole === Role::ShopManager && $shopId === null) {
            return Response::error('shop_id is required for shop_manager role', 422);
        }

        // For shop_manager: resolve the shop's company for the authorization check
        $targetCompanyId = $companyId;
        if ($targetRole === Role::ShopManager && $shopId !== null) {
            try {
                $shopUuid = new ShopId($shopId);
            } catch (InvalidArgumentException) {
                return Response::error('Invalid shop_id format', 422);
            }

            $shop = $this->shopRepository->findById($shopUuid);
            if ($shop === null) {
                return Response::error('Shop not found', 422);
            }

            $targetCompanyId = $shop->companyId->value;
            $companyId       = $targetCompanyId;
        }

        try {
            $this->authorizationService->authorizeRegistration($callerId, $targetRole, $targetCompanyId, $shopId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        try {
            $userId = $this->handler->handle(
                new RegisterUser($email, $password, $firstName, $lastName, $roleStr, $companyId, $shopId, $phoneNumber),
            );
        } catch (UserAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['id' => $userId->value], 201);
    }
}
