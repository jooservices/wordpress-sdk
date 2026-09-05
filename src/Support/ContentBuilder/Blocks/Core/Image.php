<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Image extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly int $mediaId,
        public readonly string $src = '',
        public readonly string $alt = '',
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'image';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        return array_merge([
            'id' => $this->mediaId,
            'sizeSlug' => 'full',
            'linkDestination' => 'none',
        ], $this->attributes);
    }

    protected function getContent(): string
    {
        return sprintf(
            '<figure class="wp-block-image size-full"><img src="%s" alt="%s" class="wp-image-%d"/></figure>',
            htmlspecialchars($this->src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($this->alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $this->mediaId,
        );
    }
}
