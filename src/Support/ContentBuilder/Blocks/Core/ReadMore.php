<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

/**
 * The `core/more` block (classic `<!--more-->` teaser).
 */
final class ReadMore extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $customText = '',
        public readonly bool $noTeaser = false,
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'more';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        $attributes = $this->attributes;

        if ($this->customText !== '') {
            $attributes['customText'] = $this->customText;
        }

        if ($this->noTeaser) {
            $attributes['noTeaser'] = true;
        }

        return $attributes;
    }

    protected function getContent(): string
    {
        return $this->customText !== ''
            ? sprintf('<!--more %s-->', $this->customText)
            : '<!--more-->';
    }
}
