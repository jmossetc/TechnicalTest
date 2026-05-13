<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Application\Query\RegisterUser;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Role;
use Mossetc\TechnicalTest\Auth\Domain\UserRoleRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Response;

#[AsHttpController(route: 'register')]
final readonly class RegisterUserController implements ControllerInterface
{
    public function __construct(
        private RegisterUserHandler $handler,
        private JwtAuthMiddleware $auth,
        private UserRoleRepositoryInterface $roleRepository,
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

        // Validate role value
        if ($roleStr !== null) {
            $targetRole = Role::tryFrom($roleStr);
            if ($targetRole === null) {
                return Response::error("Invalid role: {$roleStr}", 422);
            }

            // Validate required scope fields
            if ($targetRole === Role::CompanyManager && $companyId === null) {
                return Response::error('company_id is required for company_manager role', 422);
            }
            if ($targetRole === Role::ShopManager && $shopId === null) {
                return Response::error('shop_id is required for shop_manager role', 422);
            }
        }

        // Authorization check
        $callerRoles = $this->roleRepository->findByUserId($callerId);
        $error = $this->authorize($callerRoles, $roleStr, $companyId, $shopId);
        if ($error !== null) {
            return Response::error($error, 403);
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

    /**
     * @param list<\Mossetc\TechnicalTest\Auth\Domain\UserRole> $callerRoles
     */
    private function authorize(array $callerRoles, ?string $roleStr, ?string $companyId, ?string $shopId): ?string
    {
        // Check if caller has any role at all
        if ($callerRoles === []) {
            return 'You do not have permission to create accounts';
        }

        $isAdmin = false;
        /** @var list<string> $managedCompanyIds */
        $managedCompanyIds = [];
        /** @var list<string> $managedShopIds */
        $managedShopIds = [];

        foreach ($callerRoles as $role) {
            if ($role->role === Role::Admin) {
                $isAdmin = true;
            }
            if ($role->role === Role::CompanyManager && $role->companyId !== null) {
                $managedCompanyIds[] = $role->companyId;
            }
            if ($role->role === Role::ShopManager && $role->shopId !== null) {
                $managedShopIds[] = $role->shopId;
            }
        }

        // Admin can do anything
        if ($isAdmin) {
            return null;
        }

        $targetRole = $roleStr !== null ? Role::tryFrom($roleStr) : null;

        // Non-admin cannot create admins
        if ($targetRole === Role::Admin) {
            return 'Only admins can create admin accounts';
        }

        // Company manager authorization
        if ($managedCompanyIds !== []) {
            if ($targetRole === Role::CompanyManager) {
                if ($companyId !== null && in_array($companyId, $managedCompanyIds, true)) {
                    return null;
                }
                return 'You can only create company managers for your own companies';
            }
            if ($targetRole === Role::ShopManager && $shopId !== null) {
                $shopCompanyId = $this->roleRepository->findCompanyIdByShopId($shopId);
                if ($shopCompanyId !== null && in_array($shopCompanyId, $managedCompanyIds, true)) {
                    return null;
                }
                return 'You can only create shop managers for shops belonging to your companies';
            }
            // No role specified — just creating a plain account
            if ($targetRole === null) {
                return null;
            }
        }

        // Shop manager authorization
        if ($managedShopIds !== []) {
            if ($targetRole === Role::ShopManager && $shopId !== null) {
                if (in_array($shopId, $managedShopIds, true)) {
                    return null;
                }
                return 'You can only create shop managers for your own shops';
            }
            if ($targetRole === Role::CompanyManager) {
                return 'Shop managers cannot create company manager accounts';
            }
            // No role specified — just creating a plain account
            if ($targetRole === null) {
                return null;
            }
        }

        return 'You do not have permission to perform this action';
    }
}
