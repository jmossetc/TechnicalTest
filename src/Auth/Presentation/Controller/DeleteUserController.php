<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'delete_user')]
final readonly class DeleteUserController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware       $auth,
        private UserRepositoryInterface $userRepository,
        private UserAuthorization       $authorizationService,
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

        $targetIdStr = $request->attributes['id'] ?? '';

        try {
            $targetId = new UserId($targetIdStr);
        } catch (InvalidArgumentException) {
            return Response::error('Invalid user ID', 422);
        }

        $target = $this->userRepository->findById($targetId);
        if ($target === null) {
            return Response::error('User not found', 404);
        }

        try {
            $this->authorizationService->authorizeDeletion($callerId, $target);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $this->userRepository->delete($targetId);

        return Response::json(['deleted' => true]);
    }
}
