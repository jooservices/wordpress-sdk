<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\AbstractBlock;

final class Heading extends AbstractBlock
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly string $text,
        public readonly int $level = 2,
        public readonly array $attributes = [],
        private readonly bool $escapeText = true,
    ) {
        if ($level < 1 || $level > 6) {
            throw new InvalidArgumentException('Heading level must be between 1 and 6.');
        }
    }

    protected function getName(): string
    {
        return 'heading';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(): array
    {
        $attributes = $this->attributes;

        if ($this->level !== 2) {
            $attributes['level'] = $this->level;
        }

        return $attributes;
    }

    protected function getContent(): string
    {
        $text = $this->escapeText
            ? htmlspecialchars($this->text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : $this->text;

        return sprintf('<h%d>%s</h%d>', $this->level, $text, $this->level);
    }
}
