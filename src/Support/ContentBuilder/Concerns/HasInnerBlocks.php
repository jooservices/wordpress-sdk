<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Concerns;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;

/**
 * Adds inner-block management to container blocks.
 */
trait HasInnerBlocks
{
    /**
     * @var list<BlockInterface>
     */
    private array $innerBlocks = [];

    public function addBlock(BlockInterface $block): static
    {
        $this->innerBlocks[] = $block;

        return $this;
    }

    public function renderInnerBlocks(): string
    {
        return implode("\n\n", array_map(
            static fn(BlockInterface $block): string => $block->render(),
            $this->innerBlocks,
        ));
    }

    public function renderInnerBlocksHtml(): string
    {
        return implode("\n\n", array_map(
            static fn(BlockInterface $block): string => $block->toHtml(),
            $this->innerBlocks,
        ));
    }

    /**
     * @return list<BlockInterface>
     */
    public function getInnerBlocks(): array
    {
        return $this->innerBlocks;
    }
}
