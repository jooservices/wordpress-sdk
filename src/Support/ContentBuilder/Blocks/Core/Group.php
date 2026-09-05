<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Concerns\HasInnerBlocks;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContainerBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;

final class Group extends ContainerBlock
{
    use HasInnerBlocks;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $tagName = 'div',
        array $attributes = [],
        BlockInterface ...$blocks,
    ) {
        if (! in_array($tagName, ['div', 'header', 'main', 'section', 'article', 'aside', 'footer'], true)) {
            throw new InvalidArgumentException('Group tag name is not supported.');
        }

        parent::__construct([...$attributes, 'tagName' => $tagName]);

        foreach ($blocks as $block) {
            $this->addBlock($block);
        }
    }

    protected function getName(): string
    {
        return 'group';
    }

    protected function getContent(): string
    {
        return sprintf(
            '<%s class="wp-block-group">%s</%s>',
            $this->tagName,
            $this->renderInnerBlocks(),
            $this->tagName,
        );
    }
}
