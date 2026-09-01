<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress taxonomy definition (`/wp/v2/taxonomies`).
 */
final class Taxonomy extends Dto
{
    /**
     * @param list<string> $types
     * @param array<string, mixed> $labels
     */
    public function __construct(
        public readonly string $slug = '',
        public readonly string $name = '',
        public readonly array $types = [],
        public readonly string $rest_base = '',
        public readonly bool $hierarchical = false,
        public readonly string $description = '',
        public readonly string $rest_namespace = '',
        public readonly array $labels = [],
    ) {}
}
