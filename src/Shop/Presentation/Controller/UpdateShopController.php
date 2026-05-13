<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Application\Command\UpdateShop;
use Mossetc\TechnicalTest\Shop\Application\Handler\GetShopHandler;
use Mossetc\TechnicalTest\Shop\Application\Handler\UpdateShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'update_shop')]
final readonly class UpdateShopController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware $auth,
        private UserAuthorization $authorization,
        private GetShopHandler $getHandler,
        private UpdateShopHandler $handler,
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

        $id = $request->attributes['id'] ?? '';

        try {
            $shop = $this->getHandler->handle($id);
        } catch (ShopNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        }

        try {
            $this->authorization->authorizeShopAccess($callerId, $shop->id->value, $shop->companyId->value);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $name = $request->stringBody('name');

        if ($name === '') {
            return Response::error('name is required', 422);
        }

        $street  = $request->stringBody('street') ?: null;
        $city    = $request->stringBody('city') ?: null;
        $zip     = $request->stringBody('zip') ?: null;
        $country = $request->stringBody('country') ?: null;

        try {
            $this->handler->handle(new UpdateShop($id, $name, $street, $city, $zip, $country));
        } catch (ShopNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (ShopAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['updated' => true]);
    }
}
