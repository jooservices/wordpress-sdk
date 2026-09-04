<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress post status (`/wp/v2/statuses`).
 */
final class Status extends Dto
{
    public function __construct(
        public readonly string $name = '',
        public readonly string $slug = '',
        public readonly bool $public = false,
        public readonly bool $protected = false,
        public readonly bool $private = false,
        public readonly bool $queryable = false,
        public readonly bool $show_in_list = false,
        public readonly bool $date_floating = false,
    ) {}
}
