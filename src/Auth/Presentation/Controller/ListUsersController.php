<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\User;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_users')]
final readonly class ListUsersController implements ControllerInterface
{
    public function __construct(
        private ListUsersHandler         $handler,
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorizationService,
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

        $requestedCompanyIds = $this->parseIdList($request->query['company_ids'] ?? '');
        $requestedShopIds    = $this->parseIdList($request->query['shop_ids'] ?? '');

        try {
            $scope = $this->authorizationService->resolveListingScope(
                $callerId,
                $requestedCompanyIds,
                $requestedShopIds,
            );
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));

        $result = $this->handler->handle(new ListUsers($page, $limit, $scope));

        return Response::json([
            'data'       => array_map(
                static fn(User $u): array => [
                    'id'         => $u->id->value,
                    'email'      => $u->email->value,
                    'first_name' => $u->firstName->value,
                    'last_name'  => $u->lastName->value,
                    'role'       => $u->role->value,
                    'company_id' => $u->companyId,
                    'shop_id'    => $u->shopId,
                ],
                $result->users,
            ),
            'pagination' => [
                'total' => $result->total,
                'page'  => $result->page,
                'limit' => $result->limit,
                'pages' => $result->pages(),
            ],
        ]);
    }

    /** @return list<string> */
    private function parseIdList(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return explode(',', $raw)
                |> array_filter(...)
                |> array_values(...);
    }
}
