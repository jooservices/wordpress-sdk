<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Write;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Support\MapsScalarQuery;

/**
 * Typed create/update body for users.
 */
final class UserPayload extends Dto implements PayloadInterface
{
    use MapsScalarQuery;

    /**
     * @param list<string>|null $roles
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public readonly ?string $username = null,
        public readonly ?string $name = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?string $url = null,
        public readonly ?string $description = null,
        public readonly ?string $locale = null,
        public readonly ?string $nickname = null,
        public readonly ?string $slug = null,
        public readonly ?string $password = null,
        public readonly ?array $roles = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->omitEmpty([
            'username' => $this->username,
            'name' => $this->name,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'url' => $this->url,
            'description' => $this->description,
            'locale' => $this->locale,
            'nickname' => $this->nickname,
            'slug' => $this->slug,
            'password' => $this->password,
            'roles' => $this->roles,
            'meta' => $this->meta,
        ]);
    }
}
