<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Application\Handler\ListShopsHandler;
use Mossetc\TechnicalTest\Shop\Domain\Model\Shop;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_shops')]
final readonly class ListShopsController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorization,
        private ListShopsHandler         $handler,
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

        $companyId = $request->attributes['companyId'] ?? '';

        try {
            $this->authorization->authorizeCompanyAccess($callerId, $companyId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));

        try {
            $result = $this->handler->handle(new ListShops($companyId, $page, $limit));
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json([
            'data'       => $result->shops,
            'pagination' => [
                'total' => $result->total,
                'page'  => $result->page,
                'limit' => $result->limit,
                'pages' => $result->pages(),
            ],
        ]);
    }
}
