<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress application password (`/wp/v2/users/{id}/application-passwords`).
 *
 * `password` is only present in the create response.
 */
final class ApplicationPassword extends Dto
{
    public function __construct(
        public readonly string $uuid = '',
        public readonly ?string $app_id = null,
        public readonly string $name = '',
        public readonly ?string $created = null,
        public readonly ?string $last_used = null,
        public readonly ?string $last_ip = null,
        public readonly ?string $password = null,
    ) {}
}
