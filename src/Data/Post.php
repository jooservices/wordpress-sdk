<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress post (`/wp/v2/posts`).
 */
class Post extends Dto
{
    /**
     * @param list<int> $categories
     * @param list<int> $tags
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly int $id = 0,
        public readonly string $date = '',
        public readonly string $date_gmt = '',
        public readonly ?RenderedContent $guid = null,
        public readonly string $modified = '',
        public readonly string $modified_gmt = '',
        public readonly string $slug = '',
        public readonly string $status = '',
        public readonly string $type = '',
        public readonly string $link = '',
        public readonly ?RenderedContent $title = null,
        public readonly ?RenderedContent $content = null,
        public readonly ?RenderedContent $excerpt = null,
        public readonly int $author = 0,
        public readonly ?int $featured_media = null,
        public readonly string $comment_status = '',
        public readonly string $ping_status = '',
        public readonly bool $sticky = false,
        public readonly string $template = '',
        public readonly string $format = '',
        public readonly array $meta = [],
        public readonly array $categories = [],
        public readonly array $tags = [],
    ) {}
}
