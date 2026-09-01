<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress post type definition (`/wp/v2/types`).
 */
final class PostType extends Dto
{
    /**
     * @param array<string, mixed> $labels
     * @param list<string> $supports
     * @param list<string> $taxonomies
     */
    public function __construct(
        public readonly string $slug = '',
        public readonly string $name = '',
        public readonly string $rest_base = '',
        public readonly bool $hierarchical = false,
        public readonly bool $viewable = true,
        public readonly string $description = '',
        public readonly string $rest_namespace = '',
        public readonly array $labels = [],
        public readonly array $supports = [],
        public readonly array $taxonomies = [],
    ) {}
}
