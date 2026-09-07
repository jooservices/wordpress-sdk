<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Fixtures;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

/**
 * Test double for verifying custom parser registrations.
 */
final class CustomBlock extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $content,
        public readonly array $attributes,
    ) {}

    protected function getName(): string
    {
        return 'custom';
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
