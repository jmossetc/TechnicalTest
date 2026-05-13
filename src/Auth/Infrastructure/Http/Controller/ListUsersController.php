<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Application\Query\ListUsers;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\User;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_users')]
final readonly class ListUsersController implements ControllerInterface
{
    public function __construct(
        private ListUsersHandler $handler,
        private JwtAuthMiddleware $auth,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->auth->authenticate($request->headers);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 401);
        } catch (InvalidTokenException) {
            return Response::error('Unauthorized', 401);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));

        $result = $this->handler->handle(new ListUsers($page, $limit));

        return Response::json([
            'data'       => array_map(
                static fn(User $user): array => [
                    'id'    => $user->id->value,
                    'email' => $user->email->value,
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
}
