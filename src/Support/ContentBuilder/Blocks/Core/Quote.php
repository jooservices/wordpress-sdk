<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Quote extends AbstractBlock
{
    private ?string $parsedCitationMarkup = null;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $content,
        public readonly string $citation = '',
        public readonly array $attributes = [],
        private readonly bool $escapeHtml = false,
    ) {}

    /**
     * Recreates a parsed quote while preserving its existing citation markup.
     *
     * @internal Used by BlockParser for Gutenberg round-tripping.
     * @param array<string, mixed> $attributes
     * @param bool $escapeHtml Set to false only when the supplied markup is trusted.
     */
    public static function fromParsedMarkup(
        string $content,
        string $citation,
        array $attributes,
        ?string $parsedCitationMarkup,
        bool $escapeHtml = true,
    ): self {
        $quote = new self($content, $citation, $attributes, escapeHtml: $escapeHtml);
        $quote->parsedCitationMarkup = $parsedCitationMarkup;

        return $quote;
    }

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
        if ($this->parsedCitationMarkup !== null) {
            $citationMarkup = $this->escapeHtml
                ? htmlspecialchars($this->parsedCitationMarkup, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $this->parsedCitationMarkup;
            $cite = sprintf('<cite>%s</cite>', $citationMarkup);
        } else {
            $citation = $this->citation;
            $citation = $this->escapeHtml
                ? htmlspecialchars($citation, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $citation;
            $cite = $citation !== '' ? sprintf('<cite>%s</cite>', $citation) : '';
        }

        $content = $this->escapeHtml
            ? htmlspecialchars($this->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : $this->content;
        $paragraphs = preg_split("/\n\s*\n/", $content) ?: [$content];
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
