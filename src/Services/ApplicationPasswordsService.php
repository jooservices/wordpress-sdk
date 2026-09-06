<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Services;

use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Data\ApplicationPassword;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Http\AbstractService;
use JOOservices\WordPress\Sdk\Pagination\PaginatedCollection;

/**
 * Application passwords of a user
 * (`/wp/v2/users/{user}/application-passwords`).
 */
final class ApplicationPasswordsService extends AbstractService
{
    /**
     * @return PaginatedCollection<ApplicationPassword>
     */
    public function list(int|string $userId): PaginatedCollection
    {
        return $this->getList($this->path($userId), ApplicationPassword::class);
    }

    public function get(int|string $userId, string $uuid): ApplicationPassword
    {
        /** @var ApplicationPassword */
        return $this->getItem($this->path($userId, $uuid), ApplicationPassword::class);
    }

    /**
     * The raw generated password is only present in the create response.
     * Store it immediately and never log it.
     *
     * @param array<string, mixed>|PayloadInterface $payload
     */
    public function create(int|string $userId, array|PayloadInterface $payload): ApplicationPassword
    {
        /** @var ApplicationPassword */
        return $this->createItem($this->path($userId), $this->payloadArray($payload), ApplicationPassword::class);
    }

    /**
     * Rename (or otherwise update) an application password. WordPress does
     * not rotate the secret on this route.
     *
     * @param array<string, mixed>|PayloadInterface $payload
     */
    public function update(int|string $userId, string $uuid, array|PayloadInterface $payload): ApplicationPassword
    {
        /** @var ApplicationPassword */
        return $this->updateItem(
            $this->path($userId, $uuid),
            $this->payloadArray($payload),
            ApplicationPassword::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int|string $userId, string $uuid): array
    {
        return $this->requestArray('DELETE', $this->path($userId, $uuid));
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteAll(int|string $userId): array
    {
        return $this->requestArray('DELETE', $this->path($userId));
    }

    public function introspect(int|string $userId = 'me'): ApplicationPassword
    {
        /** @var ApplicationPassword */
        return $this->getItem($this->path($userId, 'introspect'), ApplicationPassword::class);
    }

    private function path(int|string $userId, ?string $uuid = null): string
    {
        $path = Endpoint::USERS->withChild($userId, 'application-passwords');

        return $uuid === null ? $path : $path . '/' . $uuid;
    }
}
