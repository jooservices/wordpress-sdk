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
     */
    public function __construct(
        public readonly int $id = 0,
        public readonly string $date = '',
        public readonly ?RenderedContent $guid = null,
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
    ) {}
}
