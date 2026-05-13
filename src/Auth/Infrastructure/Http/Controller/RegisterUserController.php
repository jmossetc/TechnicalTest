<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\RegisterUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\RegisterUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\UserAlreadyExistsException;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Response;

final readonly class RegisterUserController implements ControllerInterface
{
    public function __construct(private RegisterUserHandler $handler) {}

    public function __invoke(Request $request): Response
    {
        $email    = $request->stringBody('email');
        $password = $request->stringBody('password');

        if ($email === '' || $password === '') {
            return Response::error('email and password are required', 422);
        }

        try {
            $userId = $this->handler->handle(new RegisterUser($email, $password));
        } catch (UserAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['id' => $userId->value], 201);
    }
}
