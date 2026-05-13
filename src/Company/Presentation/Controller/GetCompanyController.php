<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorization;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Company\Application\Handler\GetCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'get_company')]
final readonly class GetCompanyController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware $auth,
        private UserAuthorization $authorization,
        private GetCompanyHandler $handler,
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
            $this->authorization->authorizeCompanyAccess($callerId, $id);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        try {
            $company = $this->handler->handle($id);
        } catch (CompanyNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 400);
        }

        return Response::json([
            'id'         => $company->id->value,
            'name'       => $company->name->value,
            'created_at' => $company->createdAt->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => $company->updatedAt->format('Y-m-d\TH:i:s\Z'),
        ]);
    }
}
