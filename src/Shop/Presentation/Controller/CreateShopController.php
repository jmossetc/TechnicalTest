<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Application\Command\CreateShop;
use Mossetc\TechnicalTest\Shop\Application\Handler\CreateShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopAlreadyExistsException;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'create_shop')]
final readonly class CreateShopController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorization,
        private CreateShopHandler        $handler,
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

        $companyId = $request->attributes['companyId'] ?? '';

        try {
            $this->authorization->authorizeCompanyAccess($callerId, $companyId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $name = $request->stringBody('name');

        if ($name === '') {
            return Response::error('name is required', 422);
        }

        $latRaw = $request->body['latitude']  ?? null;
        $lngRaw = $request->body['longitude'] ?? null;

        try {
            $id = $this->handler->handle(new CreateShop(
                companyId:    $companyId,
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
                isDigital:    (bool) ($request->body['is_digital'] ?? false),
            ));
        } catch (ShopAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['id' => $id->value], 201);
    }
}
