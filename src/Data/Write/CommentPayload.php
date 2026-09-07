<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Data\Write;

use JOOservices\Dto\Core\Dto;
use JOOservices\WordPress\Sdk\Contracts\Writable\PayloadInterface;
use JOOservices\WordPress\Sdk\Support\MapsScalarQuery;

/**
 * Typed create/update body for comments.
 */
final class CommentPayload extends Dto implements PayloadInterface
{
    use MapsScalarQuery;

    /**
     * @param array<string, mixed>|null $meta
     */
    public function __construct(
        public readonly ?int $post = null,
        public readonly ?int $parent = null,
        public readonly ?string $content = null,
        public readonly ?string $authorName = null,
        public readonly ?string $authorEmail = null,
        public readonly ?string $authorUrl = null,
        public readonly ?int $author = null,
        public readonly ?string $status = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->omitEmpty([
            'post' => $this->post,
            'parent' => $this->parent,
            'content' => $this->content,
            'author_name' => $this->authorName,
            'author_email' => $this->authorEmail,
            'author_url' => $this->authorUrl,
            'author' => $this->author,
            'status' => $this->status,
            'meta' => $this->meta,
        ]);
    }
}
