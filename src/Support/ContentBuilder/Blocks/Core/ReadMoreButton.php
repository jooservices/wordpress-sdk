<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

/**
 * The `core/read-more` block (Gutenberg's read-more link).
 */
final class ReadMoreButton extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $content = '',
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'read-more';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        return $this->attributes;
    }

    protected function getContent(): string
    {
        return $this->content;
    }
}
