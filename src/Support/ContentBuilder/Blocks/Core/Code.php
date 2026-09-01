<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Code extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $code,
        public readonly array $attributes = [],
    ) {}

    protected function getName(): string
    {
        return 'code';
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
            '<pre class="wp-block-code"><code>%s</code></pre>',
            htmlspecialchars($this->code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
}
