<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Infrastructure\Http\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\LoginUser;
use Mossetc\TechnicalTest\Auth\Application\Handler\LoginUserHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidCredentialsException;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Auth\Infrastructure\Http\Response;

final readonly class LoginUserController implements ControllerInterface
{
    public function __construct(private LoginUserHandler $handler) {}

    public function __invoke(Request $request): Response
    {
        $email    = $request->stringBody('email');
        $password = $request->stringBody('password');

        if ($email === '' || $password === '') {
            return Response::error('email and password are required', 422);
        }

        try {
            $token = $this->handler->handle(new LoginUser($email, $password));
        } catch (InvalidCredentialsException $e) {
            return Response::error($e->getMessage(), 401);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['token' => $token->value]);
    }
}
