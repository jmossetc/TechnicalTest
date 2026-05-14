<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Presentation\Controller;

use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Company\Application\Command\UpdateCompany;
use Mossetc\TechnicalTest\Company\Application\Handler\UpdateCompanyHandler;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyAlreadyExistsException;
use Mossetc\TechnicalTest\Company\Domain\Exception\CompanyNotFoundException;
use Mossetc\TechnicalTest\Company\Domain\Service\CompanyInputValidatorService;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'update_company')]
final readonly class UpdateCompanyController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware            $auth,
        private UserAuthorizationService     $authorization,
        private UpdateCompanyHandler         $handler,
        private CompanyInputValidatorService $validator,
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
            $companyInput = $this->validator->validate($request->body);
        } catch (InvalidArgumentException $e) {
            return Response::error('name is required', 422);
        }

        $isActiveRaw = $request->body['is_active'] ?? null;
        $isActive    = is_bool($isActiveRaw) ? $isActiveRaw : null;

        try {
            $this->handler->handle(UpdateCompany::fromCompanyInput($companyInput, $id, $isActive));
        } catch (CompanyNotFoundException $e) {
            return Response::error($e->getMessage(), 404);
        } catch (CompanyAlreadyExistsException $e) {
            return Response::error($e->getMessage(), 409);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        return Response::json(['updated' => true]);
    }
}
