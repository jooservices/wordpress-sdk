<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress user (`/wp/v2/users`).
 */
final class User extends Dto
{
    /**
     * @param array<string, string> $avatar_urls
     * @param array<string, mixed> $meta
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
    ) {}
}
