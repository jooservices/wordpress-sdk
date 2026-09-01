<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder;

use JsonException;
use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;

/**
 * Base block: serializes to Gutenberg comment-delimited markup.
 *
 * Empty-content blocks serialize self-closing (`<!-- wp:name /-->`);
 * blocks with content use the open/close pair.
 */
abstract class AbstractBlock implements BlockInterface
{
    public function render(): string
    {
        $name = $this->getName();
        $content = $this->getContent();
        $json = $this->attributesJson();

        if ($content === '') {
            return sprintf('<!-- wp:%s%s /-->', $name, $json);
        }

        return sprintf(
            "<!-- wp:%s%s -->\n%s\n<!-- /wp:%s -->",
            $name,
            $json,
            $content,
            $name,
        );
    }

    public function toHtml(): string
    {
        return $this->getContent();
    }

    abstract protected function getName(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function getAttributes(): array;

    abstract protected function getContent(): string;

    private function attributesJson(): string
    {
        $attributes = $this->getAttributes();

        if ($attributes === []) {
            return '';
        }

        try {
            return ' ' . json_encode($attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Block attributes must be valid JSON values.', 0, $exception);
        }
    }
}
