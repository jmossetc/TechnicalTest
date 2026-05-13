<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserNotFoundException;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserId;
use Mossetc\TechnicalTest\Auth\Domain\Repository\UserRepositoryInterface;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserDeletion;
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
        private UserDeletion            $userDeletionService,
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

        try {
            $this->userDeletionService->deleteUser($targetId, $callerId);
        } catch (UserNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $this->userRepository->delete($targetId);

        return Response::json(['deleted' => true]);
    }
}
