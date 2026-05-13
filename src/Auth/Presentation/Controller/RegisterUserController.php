<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'register')]
final readonly class RegisterUserController implements ControllerInterface
{
    public function __construct(
        private RegisterUserHandler $handler,
        private JwtAuthMiddleware   $auth,
        private UserAuthorization   $authorizationService,
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

        $email    = $request->stringBody('email');
        $password = $request->stringBody('password');

        if ($email === '' || $password === '') {
            return Response::error('email and password are required', 422);
        }

        $roleStr   = $request->stringBody('role') ?: null;
        $companyId = $request->stringBody('company_id') ?: null;
        $shopId    = $request->stringBody('shop_id') ?: null;

        $targetRole = null;

        if ($roleStr !== null) {
            $targetRole = Role::tryFrom($roleStr);
            if ($targetRole === null) {
                return Response::error("Invalid role: {$roleStr}", 422);
            }

            if ($targetRole === Role::CompanyManager && $companyId === null) {
                return Response::error('company_id is required for company_manager role', 422);
            }
            if ($targetRole === Role::ShopManager && $shopId === null) {
                return Response::error('shop_id is required for shop_manager role', 422);
            }
        }

        try {
            $this->authorizationService->authorizeRegistration($callerId, $targetRole, $companyId, $shopId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        try {
            $userId = $this->handler->handle(new RegisterUser($email, $password, $roleStr, $companyId, $shopId));
        } catch (UserAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['id' => $userId->value], 201);
    }
}
