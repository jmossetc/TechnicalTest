<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
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
use Mossetc\TechnicalTest\Shop\Domain\Service\ShopInputValidatorService;

#[AsHttpController(route: 'update_shop')]
final readonly class UpdateShopController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware         $auth,
        private UserAuthorizationService  $authorization,
        private GetShopHandler            $getHandler,
        private UpdateShopHandler         $handler,
        private ShopInputValidatorService $validator,
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

        try {
            $this->validator->validate($request->body);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        $name   = $request->stringBody('name');
        $latRaw = $request->body['latitude']  ?? null;
        $lngRaw    = $request->body['longitude'] ?? null;
        $isDigital = isset($request->body['is_digital']) ? (bool) $request->body['is_digital'] : null;
        $isActive  = isset($request->body['is_active'])  ? (bool) $request->body['is_active']  : null;

        try {
            $this->handler->handle(new UpdateShop(
                id:           $id,
                name:         $name,
                email:        $request->stringBody('email') ?: null,
                phoneNumber:  $request->stringBody('phone_number') ?: null,
                addressLine1: $request->stringBody('address_line_1') ?: null,
                addressLine2: $request->stringBody('address_line_2') ?: null,
                city:         $request->stringBody('city') ?: null,
                postalCode:   $request->stringBody('postal_code') ?: null,
                country:      $request->stringBody('country') ?: null,
                latitude:     is_numeric($latRaw) ? (float) $latRaw : null,
                longitude:    is_numeric($lngRaw) ? (float) $lngRaw : null,
                isDigital:    $isDigital,
                isActive:     $isActive,
            ));
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
