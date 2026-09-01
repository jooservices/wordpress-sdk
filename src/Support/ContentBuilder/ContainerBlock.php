<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;

/**
 * Base for blocks that hold inner blocks.
 */
abstract class ContainerBlock extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        protected readonly array $attributes = [],
    ) {}

    abstract public function addBlock(BlockInterface $block): static;

    /**
     * @return list<BlockInterface>
     */
    abstract public function getInnerBlocks(): array;

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        return $this->attributes;
    }
}
