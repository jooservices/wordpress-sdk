<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data;

use JOOservices\Dto\Core\Dto;

/**
 * WordPress media item (`/wp/v2/media`).
 */
final class Media extends Dto
{
    /**
     * @param array<string, mixed> $media_details
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly int $id = 0,
        public readonly string $date = '',
        public readonly ?string $date_gmt = null,
        public readonly ?RenderedContent $guid = null,
        public readonly string $modified = '',
        public readonly string $modified_gmt = '',
        public readonly string $slug = '',
        public readonly string $status = '',
        public readonly string $type = '',
        public readonly string $link = '',
        public readonly ?RenderedContent $title = null,
        public readonly ?RenderedContent $caption = null,
        public readonly ?RenderedContent $description = null,
        public readonly string $alt_text = '',
        public readonly string $media_type = '',
        public readonly string $mime_type = '',
        public readonly array $media_details = [],
        public readonly int $author = 0,
        public readonly string $source_url = '',
        public readonly ?int $post = null,
        public readonly string $comment_status = '',
        public readonly string $ping_status = '',
        public readonly string $template = '',
        public readonly array $meta = [],
    ) {}
}
