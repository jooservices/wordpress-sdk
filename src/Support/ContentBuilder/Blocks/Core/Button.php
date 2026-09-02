<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Button extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $text,
        public readonly string $url = '',
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'button';
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
        return sprintf(
            '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
            htmlspecialchars($this->url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $this->text,
        );
    }
}
