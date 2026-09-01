<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Concerns\HasInnerBlocks;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContainerBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;

final class Column extends ContainerBlock
{
    use HasInnerBlocks;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        BlockInterface ...$blocks,
    ) {
        parent::__construct($attributes);

        foreach ($blocks as $block) {
            $this->addBlock($block);
        }
    }

    protected function getName(): string
    {
        return 'column';
    }

    protected function getContent(): string
    {
        return sprintf('<div class="wp-block-column">%s</div>', $this->renderInnerBlocks());
    }
}
