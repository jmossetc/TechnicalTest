<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Application\Handler\GetShopHandler;
use Mossetc\TechnicalTest\Shop\Domain\Exception\ShopNotFoundException;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'get_shop')]
final readonly class GetShopController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware $auth,
        private UserAuthorization $authorization,
        private GetShopHandler $handler,
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
            $shop = $this->handler->handle($id);
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

        return Response::json([
            'id'         => $shop->id->value,
            'company_id' => $shop->companyId->value,
            'name'       => $shop->name->value,
            'street'     => $shop->address->street,
            'city'       => $shop->address->city,
            'zip'        => $shop->address->zip,
            'country'    => $shop->address->country,
            'created_at' => $shop->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $shop->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ]);
    }
}
