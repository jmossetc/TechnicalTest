<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Shop\Presentation\Controller;

use DateTimeImmutable;
use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shop\Application\Command\ListShops;
use Mossetc\TechnicalTest\Shop\Application\Handler\ListShopsHandler;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSearchCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortCriteria;
use Mossetc\TechnicalTest\Shop\Domain\Model\ShopSortField;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_shops')]
final readonly class ListShopsController implements ControllerInterface
{
    public function __construct(
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorization,
        private ListShopsHandler         $handler,
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
            $resolvedCompanyId = $this->authorization->resolveShopListingCompanyId(
                $callerId,
                $this->nullableString($request->query['company_id'] ?? ''),
            );
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));

        try {
            $criteria = $this->buildCriteria($request->query, $resolvedCompanyId);
            $sort     = $this->buildSort($request->query);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        $result = $this->handler->handle(new ListShops($page, $limit, $criteria, $sort));

        return Response::json([
            'data'       => $result->shops,
            'pagination' => [
                'total' => $result->total,
                'page'  => $result->page,
                'limit' => $result->limit,
                'pages' => $result->pages(),
            ],
        ]);
    }

    /** @param array<string, string> $query */
    private function buildCriteria(array $query, ?string $companyId): ShopSearchCriteria
    {
        $isDigital = null;
        if (isset($query['is_digital']) && $query['is_digital'] !== '') {
            $isDigital = match ($query['is_digital']) {
                'true', '1'  => true,
                'false', '0' => false,
                default      => throw new InvalidArgumentException(
                    "Invalid is_digital value '{$query['is_digital']}', expected true/false/1/0"
                ),
            };
        }

        return new ShopSearchCriteria(
            companyId:   $companyId,
            name:        $this->nullableString($query['name'] ?? ''),
            email:       $this->nullableString($query['email'] ?? ''),
            phoneNumber: $this->nullableString($query['phone_number'] ?? ''),
            city:        $this->nullableString($query['city'] ?? ''),
            postalCode:  $this->nullableString($query['postal_code'] ?? ''),
            country:     $this->nullableString($query['country'] ?? ''),
            isDigital:   $isDigital,
            createdFrom: $this->parseDate($query['created_from'] ?? ''),
            createdTo:   $this->parseDate($query['created_to'] ?? '', endOfDay: true),
        );
    }

    /** @param array<string, string> $query */
    private function buildSort(array $query): ShopSortCriteria
    {
        $field = ShopSortField::Name;
        if (isset($query['sort_by']) && $query['sort_by'] !== '') {
            $field = ShopSortField::tryFrom($query['sort_by'])
                ?? throw new InvalidArgumentException(
                    "Invalid sort_by value '{$query['sort_by']}', valid values: "
                    . implode(', ', array_column(ShopSortField::cases(), 'value'))
                );
        }

        $direction = SortDirection::Asc;
        if (isset($query['sort_direction']) && $query['sort_direction'] !== '') {
            $direction = SortDirection::tryFrom($query['sort_direction'])
                ?? throw new InvalidArgumentException(
                    "Invalid sort_direction value '{$query['sort_direction']}', expected asc or desc"
                );
        }

        return new ShopSortCriteria($field, $direction);
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
