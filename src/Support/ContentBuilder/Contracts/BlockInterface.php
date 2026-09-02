<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts;

/**
 * A serializable Gutenberg block.
 */
interface BlockInterface
{
    /**
     * Block markup with Gutenberg comment delimiters.
     */
    public function render(): string;

    /**
     * Inner HTML without the Gutenberg comment delimiters.
     */
    public function toHtml(): string;
}
