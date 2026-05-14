<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'get_user')]
final readonly class GetUserController implements ControllerInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
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

        $rawId = $request->attributes['id'] ?? '';

        try {
            $userId = new UserId($rawId);
        } catch (InvalidArgumentException) {
            return Response::error('Invalid user ID format', 400);
        }

        $user = $this->repository->findById($userId);

        if ($user === null) {
            return Response::error('User not found', 404);
        }

        return Response::json($user);
    }
}
