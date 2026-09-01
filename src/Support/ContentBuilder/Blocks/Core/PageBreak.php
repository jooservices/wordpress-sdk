<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

/**
 * The `core/nextpage` block (classic `<!--nextpage-->` page break).
 */
final class PageBreak extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'nextpage';
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
        return '<!--nextpage-->';
    }
}
