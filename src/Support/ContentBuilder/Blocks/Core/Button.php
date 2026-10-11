<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use InvalidArgumentException;
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
        private readonly bool $escapeText = true,
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
        $this->assertSafeUrlScheme();

        return sprintf(
            '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>',
            htmlspecialchars($this->url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $this->escapeText
                ? htmlspecialchars($this->text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $this->text,
        );
    }

    private function assertSafeUrlScheme(): void
    {
        $normalizedUrl = preg_replace('/[\x00-\x20\x7F]+/', '', $this->url) ?? $this->url;

        if (preg_match('/\A(?:javascript|data|vbscript):/i', $normalizedUrl) === 1) {
            throw new InvalidArgumentException('Button URLs must not use executable schemes.');
        }
    }
}
