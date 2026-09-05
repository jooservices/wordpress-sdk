<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Quote extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $content,
        public readonly string $citation = '',
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'quote';
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
        $cite = $this->citation !== '' ? sprintf('<cite>%s</cite>', $this->citation) : '';
        $paragraphs = preg_split("/\n\s*\n/", $this->content) ?: [$this->content];
        $body = '';
        foreach ($paragraphs as $paragraph) {
            $trimmed = trim($paragraph);
            if ($trimmed === '') {
                continue;
            }

            $body .= sprintf('<p>%s</p>', $trimmed);
        }

        return sprintf(
            '<blockquote class="wp-block-quote">%s%s</blockquote>',
            $body,
            $cite,
        );
    }
}
