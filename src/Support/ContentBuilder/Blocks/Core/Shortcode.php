<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Shortcode extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $shortcode,
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'shortcode';
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
        return $this->shortcode;
    }
}
