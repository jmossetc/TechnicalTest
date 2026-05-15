<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\UpdateUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\UpdateUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserNotFoundException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserUpdateInputValidatorService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'update_user')]
final readonly class UpdateUserController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware               $auth,
        private UserUpdateInputValidatorService $validator,
        private UpdateUserHandler               $handler,
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

        $targetId = $request->attributes['id'] ?? '';

        try {
            $input = $this->validator->validate($request->body);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        try {
            $this->handler->handle(new UpdateUser($callerId, $targetId, $input));
        } catch (UserNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        } catch (UserAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['updated' => true]);
    }
}
