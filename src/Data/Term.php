<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress taxonomy term (categories and tags).
 */
final class Term extends Dto
{
    /**
     * @param array<string, mixed> $meta
     * @param array<string, list<array<string, mixed>>> $_links
     * @param array<string, list<array<string, mixed>>> $_embedded
     */
    public function __construct(
        public readonly int $id = 0,
        public readonly int $count = 0,
        public readonly string $description = '',
        public readonly string $link = '',
        public readonly string $name = '',
        public readonly string $slug = '',
        public readonly string $taxonomy = '',
        public readonly int $parent = 0,
        public readonly array $meta = [],
        public readonly array $_links = [],
        public readonly array $_embedded = [],
    ) {}
}
