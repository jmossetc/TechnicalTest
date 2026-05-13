<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Domain\Service\RegistrationInputValidatorService;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'register')]
final readonly class RegisterUserController implements ControllerInterface
{
    public function __construct(
        private RegisterUserHandler               $handler,
        private JwtAuthMiddleware                 $auth,
        private UserAuthorizationService          $authorizationService,
        private RegistrationInputValidatorService $registrationInputValidatorService,
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
            $inputs = $this->registrationInputValidatorService->validate($request->body);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        try {
            $this->authorizationService->authorizeRegistration($callerId, $inputs->role, $inputs->companyId, $inputs->shopId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        try {
            $userId = $this->handler->handle(
                RegisterUser::fromRegistrationInput($inputs),
            );
        } catch (UserAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['id' => $userId->value], 201);
    }
}
