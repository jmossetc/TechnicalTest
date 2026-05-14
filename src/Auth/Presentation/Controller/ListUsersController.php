<?php

declare(strict_types=1);

namespace Mossetc\TechnicalTest\Auth\Presentation\Controller;

use DateTimeImmutable;
use InvalidArgumentException;
use Mossetc\TechnicalTest\Auth\Application\Command\ListUsers;
use Mossetc\TechnicalTest\Auth\Application\Handler\ListUsersHandler;
use Mossetc\TechnicalTest\Auth\Domain\Exception\ForbiddenException;
use Mossetc\TechnicalTest\Auth\Domain\Exception\InvalidTokenException;
use Mossetc\TechnicalTest\Auth\Domain\Model\Role;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSearchCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortCriteria;
use Mossetc\TechnicalTest\Auth\Domain\Model\UserSortField;
use Mossetc\TechnicalTest\Auth\Domain\Service\UserAuthorizationService;
use Mossetc\TechnicalTest\Auth\Infrastructure\Jwt\JwtAuthMiddleware;
use Mossetc\TechnicalTest\Shared\Domain\SortDirection;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\AsHttpController;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Controller\ControllerInterface;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Request;
use Mossetc\TechnicalTest\Shared\Infrastructure\Http\Response;

#[AsHttpController(route: 'list_users')]
final readonly class ListUsersController implements ControllerInterface
{
    public function __construct(
        private ListUsersHandler         $handler,
        private JwtAuthMiddleware        $auth,
        private UserAuthorizationService $authorizationService,
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

        $requestedCompanyIds = $this->parseIdList($request->query['company_ids'] ?? '');
        $requestedShopIds    = $this->parseIdList($request->query['shop_ids'] ?? '');

        try {
            $scope = $this->authorizationService->resolveListingScope(
                $callerId,
                $requestedCompanyIds,
                $requestedShopIds,
            );
        } catch (ForbiddenException $e) {
            return Response::error($e->getMessage(), 403);
        }

        try {
            $criteria = $this->buildCriteria($request->query);
            $sort     = $this->buildSort($request->query);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        }

        $page  = max(1, (int) ($request->query['page'] ?? '1'));
        $limit = min(100, max(1, (int) ($request->query['limit'] ?? '10')));

        $result = $this->handler->handle(new ListUsers($page, $limit, $scope, $criteria, $sort));

        return Response::json([
            'data'       => $result->users,
            'pagination' => [
                'total' => $result->total,
                'page'  => $result->page,
                'limit' => $result->limit,
                'pages' => $result->pages(),
            ],
        ]);
    }

    /** @param array<string, string> $query */
    private function buildCriteria(array $query): UserSearchCriteria
    {
        $email       = $this->nullableString($query['email'] ?? '');
        $firstName   = $this->nullableString($query['first_name'] ?? '');
        $lastName    = $this->nullableString($query['last_name'] ?? '');
        $phoneNumber = $this->nullableString($query['phone_number'] ?? '');

        $role = null;
        if (isset($query['role']) && $query['role'] !== '') {
            $role = Role::tryFrom($query['role']);
            if ($role === null) {
                throw new InvalidArgumentException("Invalid role '{$query['role']}'");
            }
        }

        $isActive = null;
        if (isset($query['is_active']) && $query['is_active'] !== '') {
            $isActive = match ($query['is_active']) {
                'true', '1'  => true,
                'false', '0' => false,
                default      => throw new InvalidArgumentException(
                    "Invalid is_active value '{$query['is_active']}', expected true/false/1/0"
                ),
            };
        }

        return new UserSearchCriteria(
            email:         $email,
            firstName:     $firstName,
            lastName:      $lastName,
            phoneNumber:   $phoneNumber,
            role:          $role,
            isActive:      $isActive,
            createdFrom:   $this->parseDate($query['created_from'] ?? ''),
            createdTo:     $this->parseDate($query['created_to'] ?? '', endOfDay: true),
            lastLoginFrom: $this->parseDate($query['last_login_from'] ?? ''),
            lastLoginTo:   $this->parseDate($query['last_login_to'] ?? '', endOfDay: true),
        );
    }

    /** @param array<string, string> $query */
    private function buildSort(array $query): UserSortCriteria
    {
        $field = UserSortField::Email;
        if (isset($query['sort_by']) && $query['sort_by'] !== '') {
            $field = UserSortField::tryFrom($query['sort_by'])
                ?? throw new InvalidArgumentException(
                    "Invalid sort_by value '{$query['sort_by']}', valid values: "
                    . implode(', ', array_column(UserSortField::cases(), 'value'))
                );
        }

        $direction = SortDirection::Asc;
        if (isset($query['sort_direction']) && $query['sort_direction'] !== '') {
            $direction = SortDirection::tryFrom($query['sort_direction'])
                ?? throw new InvalidArgumentException(
                    "Invalid sort_direction value '{$query['sort_direction']}', expected asc or desc"
                );
        }

        return new UserSortCriteria($field, $direction);
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

    /** @return list<string> */
    private function parseIdList(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return explode(',', $raw)
                |> array_filter(...)
                |> array_values(...);
    }
}
