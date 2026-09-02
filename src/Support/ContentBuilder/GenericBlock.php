<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder;

/**
 * A block by explicit name, attributes, and content. The escape hatch for
 * blocks without a dedicated class.
 */
final class GenericBlock extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly array $attributes = [],
        public readonly string $content = '',
    ) {}

    protected function getName(): string
    {
        return $this->name;
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
