<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Company\Presentation\Controller;

use DateTimeImmutable;
use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Company\Application\Command\ListCompanies;
use Mossetc\TechnicalTest\Company\Application\Handler\ListCompaniesHandler;
use Mossetc\TechnicalTest\Company\Domain\Model\CompanySearchCriteria;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_companies')]
final readonly class ListCompaniesController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorization,
        private ListCompaniesHandler     $handler,
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
            $this->authorization->authorizeAdminOnlyAction($callerId);
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));

        try {
            $criteria = $this->buildCriteria($request->query);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        $result = $this->handler->handle(new ListCompanies($page, $limit, $criteria));

        return Response::json([
            'data'       => $result->companies,
            'pagination' => [
                'total' => $result->total,
                'page'  => $result->page,
                'limit' => $result->limit,
                'pages' => $result->pages(),
            ],
        ]);
    }

    /** @param array<string, string> $query */
    private function buildCriteria(array $query): CompanySearchCriteria
    {
        return new CompanySearchCriteria(
            name:        $this->nullableString($query['name'] ?? ''),
            email:       $this->nullableString($query['email'] ?? ''),
            phoneNumber: $this->nullableString($query['phone_number'] ?? ''),
            city:        $this->nullableString($query['city'] ?? ''),
            postalCode:  $this->nullableString($query['postal_code'] ?? ''),
            country:     $this->nullableString($query['country'] ?? ''),
            createdFrom: $this->parseDate($query['created_from'] ?? ''),
            createdTo:   $this->parseDate($query['created_to'] ?? '', endOfDay: true),
        );
    }

    private function nullableString(string $value): ?string
    {
        return $value !== '' ? $value : null;
    }

    private function parseDate(string $raw, bool $endOfDay = false): ?DateTimeImmutable
    {
        if ($raw === '') {
            return null;
        }

        $dt     = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
        $errors = DateTimeImmutable::getLastErrors();

        if ($dt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException("Invalid date format '{$raw}', expected Y-m-d");
        }

        return $endOfDay ? $dt->setTime(23, 59, 59) : $dt->setTime(0, 0, 0);
    }
}
