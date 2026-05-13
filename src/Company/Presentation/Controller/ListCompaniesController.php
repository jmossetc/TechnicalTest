<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use Mossetc\TechnicalTest\Company\Application\Handler\ListCompaniesHandler;
use Mossetc\TechnicalTest\Company\Domain\Model\Company;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_companies')]
final readonly class ListCompaniesController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorization,
        private ListCompaniesHandler     $handler,
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

        try {
            $this->authorization->authorizeAdminOnlyAction($callerId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));
        $name  = isset($request->query['name']) && $request->query['name'] !== ''
            ? $request->query['name']
            : null;

        $result = $this->handler->handle(new ListCompanies($page, $limit, $name));

        return Response::json([
            'data'       => array_map(
                static fn(Company $c): array => [
                    'id'            => $c->id->value,
                    'name'          => $c->name->value,
                    'email'         => $c->email,
                    'phone_number'  => $c->phoneNumber,
                    'city'          => $c->city,
                    'country'       => $c->country,
                    'is_active'     => $c->isActive,
                    'created_at'    => $c->createdAt->format('Y-m-d\TH:i:s\Z'),
                    'updated_at'    => $c->updatedAt->format('Y-m-d\TH:i:s\Z'),
                ],
                $result->companies,
            ),
            'pagination' => [
                'total' => $result->total,
                'page'  => $result->page,
                'limit' => $result->limit,
                'pages' => $result->pages(),
            ],
        ]);
    }
}
