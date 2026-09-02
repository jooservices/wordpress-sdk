<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Contracts\QueryParametersInterface;
use JOOservices\WordPress\Sdk\Data\User;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;

/**
 * @extends AbstractCrudService<User>
 */
final class UsersService extends AbstractCrudService
{
    /**
     * The authenticated user (`/wp/v2/users/me`).
     *
     * @param array<string, mixed>|QueryParametersInterface|null $params
     */
    public function me(array|QueryParametersInterface|null $params = null): User
    {
        /** @var User */
        return $this->getItem(
            Endpoint::USERS_ME->path(),
            User::class,
            ['query' => $this->normalizeQueryParameters($params)],
        );
    }

    protected function dtoClass(): string
    {
        return User::class;
    }

    protected function listPath(): string
    {
        return Endpoint::USERS->path();
    }
}
