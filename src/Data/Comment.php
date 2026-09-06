<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress comment (`/wp/v2/comments`).
 */
final class Comment extends Dto
{
    /**
     * @param array<string, string> $author_avatar_urls
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly int $id = 0,
        public readonly int $post = 0,
        public readonly int $parent = 0,
        public readonly int $author = 0,
        public readonly string $author_name = '',
        public readonly ?string $author_email = null,
        public readonly string $author_url = '',
        public readonly ?string $author_ip = null,
        public readonly ?string $author_user_agent = null,
        public readonly string $date = '',
        public readonly string $date_gmt = '',
        public readonly ?RenderedContent $content = null,
        public readonly string $link = '',
        public readonly string $status = '',
        public readonly string $type = '',
        public readonly array $author_avatar_urls = [],
        public readonly array $meta = [],
    ) {}
}
