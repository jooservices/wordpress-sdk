<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Raw;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

/**
 * The `core/html` block — raw HTML passthrough.
 */
final class HtmlBlock extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $html,
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'html';
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
        return $this->html;
    }
}
