<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress user (`/wp/v2/users`).
 *
 * Edit-context fields (`email`, `roles`, `capabilities`, …) are nullable so
 * view-context payloads still hydrate without casting failures.
 */
final class User extends Dto
{
    /**
     * @param array<string, string> $avatar_urls
     * @param array<string, mixed> $meta
     * @param list<string>|null $roles
     * @param array<string, bool>|null $capabilities
     * @param array<string, bool>|null $extra_capabilities
     */
    public function __construct(
        public readonly int $id = 0,
        public readonly string $name = '',
        public readonly string $url = '',
        public readonly string $description = '',
        public readonly string $link = '',
        public readonly string $slug = '',
        public readonly array $avatar_urls = [],
        public readonly array $meta = [],
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $first_name = null,
        public readonly ?string $last_name = null,
        public readonly ?string $nickname = null,
        public readonly ?string $locale = null,
        public readonly ?string $registered_date = null,
        public readonly ?array $roles = null,
        public readonly ?array $capabilities = null,
        public readonly ?array $extra_capabilities = null,
    ) {}
}
