<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress search result (`/wp/v2/search`).
 */
final class SearchResult extends Dto
{
    public function __construct(
        public readonly int|string $id = 0,
        public readonly string $title = '',
        public readonly string $url = '',
        public readonly string $type = '',
        public readonly string $subtype = '',
    ) {}
}
