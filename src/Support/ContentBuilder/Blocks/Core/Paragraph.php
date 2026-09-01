<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Paragraph extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $text,
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'paragraph';
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
        $class = '';
        if (isset($this->attributes['align']) && is_scalar($this->attributes['align'])) {
            $alignment = htmlspecialchars((string) $this->attributes['align'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $class = ' class="has-text-align-' . $alignment . '"';
        }

        return sprintf('<p%s>%s</p>', $class, $this->text);
    }
}
