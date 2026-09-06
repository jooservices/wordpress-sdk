<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Write;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Enums\OpenClosed;
use JOOservices\WordPress\Sdk\Enums\PostStatus;
use JOOservices\WordPress\Sdk\Support\MapsScalarQuery;

/**
 * Typed create/update body for posts (and custom post types using {@see Post}).
 */
final class PostPayload extends Dto implements PayloadInterface
{
    use MapsScalarQuery;

    /**
     * @param list<int>|null $categories
     * @param list<int>|null $tags
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $content = null,
        public readonly ?string $excerpt = null,
        public readonly PostStatus|string|null $status = null,
        public readonly ?string $slug = null,
        public readonly ?int $author = null,
        public readonly ?int $featuredMedia = null,
        public readonly OpenClosed|string|null $commentStatus = null,
        public readonly OpenClosed|string|null $pingStatus = null,
        public readonly ?string $format = null,
        public readonly ?bool $sticky = null,
        public readonly ?string $template = null,
        public readonly ?string $password = null,
        public readonly ?string $date = null,
        public readonly ?int $parent = null,
        public readonly ?int $menuOrder = null,
        public readonly ?array $categories = null,
        public readonly ?array $tags = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->omitEmpty([
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->scalar($this->status),
            'slug' => $this->slug,
            'author' => $this->author,
            'featured_media' => $this->featuredMedia,
            'comment_status' => $this->scalar($this->commentStatus),
            'ping_status' => $this->scalar($this->pingStatus),
            'format' => $this->format,
            'sticky' => $this->sticky,
            'template' => $this->template,
            'password' => $this->password,
            'date' => $this->date,
            'parent' => $this->parent,
            'menu_order' => $this->menuOrder,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'meta' => $this->meta,
        ]);
    }
}
