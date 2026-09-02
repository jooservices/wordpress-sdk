<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder\Parser;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\BlockRegistry;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Code;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Group;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Heading;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Image;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\PageBreak;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMore;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMoreButton;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Separator;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Shortcode;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Raw\HtmlBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContainerBlock;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;

/**
 * Round-trips Gutenberg comment-delimited markup back into blocks.
 *
 * Registered leaf blocks are reconstructed from attributes and inner
 * content; unregistered blocks become {@see GenericBlock}; stray text and
 * malformed markup degrade to {@see HtmlBlock} instead of being dropped.
 */
final class BlockParser
{
    /**
     * @return list<BlockInterface>
     */
    public function parse(string $content, BlockRegistry $registry): array
    {
        $blocks = [];
        $offset = 0;
        $length = strlen($content);

        while ($offset < $length) {
            $headerStart = strpos($content, '<!-- wp:', $offset);

            if ($headerStart === false) {
                $this->appendText($blocks, substr($content, $offset));

                break;
            }

            $this->appendText($blocks, substr($content, $offset, $headerStart - $offset));

            $headerEnd = strpos($content, '-->', $headerStart);
            if ($headerEnd === false) {
                $this->appendText($blocks, substr($content, $headerStart));

                break;
            }

            $header = substr($content, $headerStart + 8, $headerEnd - $headerStart - 8);
            $name = strtok($header, ' ');
            if ($name === false) {
                $this->appendText($blocks, substr($content, $headerStart, $headerEnd + 3 - $headerStart));

                $offset = $headerEnd + 3;

                continue;
            }

            $selfClosing = str_ends_with($header, '/');
            $attributesJson = trim(substr($header, strlen($name)));
            if ($selfClosing) {
                $attributesJson = rtrim(substr($attributesJson, 0, -1));
            }
            $attributes = $this->decodeAttributes($attributesJson);

            if ($selfClosing) {
                $blocks[] = $this->createBlock($name, $attributes, '', $registry);

                $offset = $headerEnd + 3;

                continue;
            }

            $closer = $this->findCloser($content, $name, $headerEnd + 3);

            if ($closer === null) {
                $this->appendText($blocks, substr($content, $headerStart));

                break;
            }

            $inner = substr($content, $headerEnd + 3, $closer - ($headerEnd + 3));

            $blocks[] = $this->createBlock($name, $attributes, $inner, $registry);

            $offset = $closer + strlen("<!-- /wp:{$name} -->");
        }

        return $blocks;
    }

    /**
     * @param list<BlockInterface> $blocks
     */
    private function appendText(array &$blocks, string $text): void
    {
        if (trim($text) === '') {
            return;
        }

        $blocks[] = new HtmlBlock($text);
    }

    private function findCloser(string $content, string $name, int $offset): ?int
    {
        $openerNeedle = sprintf('<!-- wp:%s', $name);
        $closerNeedle = sprintf('<!-- /wp:%s -->', $name);
        $depth = 1;
        $cursor = $offset;

        while (true) {
            $closer = strpos($content, $closerNeedle, $cursor);
            if ($closer === false) {
                return null;
            }

            $opener = $this->findNextExactOpener($content, $name, $openerNeedle, $cursor);
            if ($opener !== null && $opener['position'] < $closer) {
                if (! $opener['self_closing']) {
                    $depth++;
                }

                $cursor = $opener['end'];

                continue;
            }

            $depth--;
            if ($depth === 0) {
                return $closer;
            }

            $cursor = $closer + strlen($closerNeedle);
        }
    }

    /**
     * @return array{position: int, end: int, self_closing: bool}|null
     */
    private function findNextExactOpener(string $content, string $name, string $needle, int $offset): ?array
    {
        $position = $offset;

        while (($position = strpos($content, $needle, $position)) !== false) {
            $headerEnd = strpos($content, '-->', $position);
            if ($headerEnd === false) {
                return null;
            }

            $header = substr($content, $position + 8, $headerEnd - $position - 8);
            if (strtok($header, ' ') === $name) {
                return [
                    'position' => $position,
                    'end' => $headerEnd + 3,
                    'self_closing' => str_ends_with($header, '/'),
                ];
            }

            $position = $headerEnd + 3;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAttributes(string $json): array
    {
        if ($json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('Block attributes contain invalid JSON.', 0, $exception);
        }

        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            throw new InvalidArgumentException('Block attributes must be a JSON object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createBlock(string $name, array $attributes, string $inner, BlockRegistry $registry): BlockInterface
    {
        $className = $this->resolveClass($name, $registry);

        if ($className === null) {
            return new GenericBlock($name, $attributes, $inner);
        }

        if (is_subclass_of($className, ContainerBlock::class)) {
            return $this->createContainer($className, $attributes, $inner, $registry);
        }

        return $this->createLeaf($name, $className, $attributes, $inner);
    }

    /**
     * @return class-string<BlockInterface>|null
     */
    private function resolveClass(string $name, BlockRegistry $registry): ?string
    {
        foreach (['core/' . $name, $name] as $candidate) {
            if ($registry->has($candidate)) {
                return $registry->get($candidate);
            }
        }

        return null;
    }

    /**
     * @param class-string<BlockInterface> $className
     * @param array<string, mixed> $attributes
     */
    private function createContainer(string $className, array $attributes, string $inner, BlockRegistry $registry): BlockInterface
    {
        if ($className === Group::class) {
            $tagName = $attributes['tagName'] ?? 'div';
            $container = new Group(is_string($tagName) ? $tagName : 'div', $attributes);
        } else {
            /** @var ContainerBlock $container */
            $container = new $className($attributes);
        }

        foreach ($this->parse($inner, $registry) as $child) {
            if ($child instanceof HtmlBlock && $this->isWrapperOnly($child->toHtml())) {
                continue;
            }

            $container->addBlock($child);
        }

        return $container;
    }

    private function isWrapperOnly(string $html): bool
    {
        $trimmed = trim($html);

        if (preg_match('/^<(?:div|section|header|footer|main|article|figure|ul|ol|blockquote|p)[^>]*>$/i', $trimmed) === 1) {
            return true;
        }

        return preg_match('/^<\/(?:div|section|header|footer|main|article|figure|ul|ol|blockquote|p)>$/i', $trimmed) === 1;
    }

    /**
     * @param class-string<BlockInterface> $className
     * @param array<string, mixed> $attributes
     */
    private function createLeaf(string $name, string $className, array $attributes, string $inner): BlockInterface
    {
        return match ($name) {
            'paragraph' => new Paragraph(trim(strip_tags($inner)), $attributes),
            'heading' => $this->createHeading($attributes, $inner),
            'image' => $this->createImage($attributes, $inner),
            'quote' => $this->createQuote($attributes, $inner),
            'code' => new Code(html_entity_decode(trim(strip_tags($inner)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $attributes),
            'shortcode' => new Shortcode(trim($inner), $attributes),
            'html' => new HtmlBlock($inner, $attributes),
            'more' => $this->createReadMore($attributes, $inner),
            'read-more' => new ReadMoreButton(trim(strip_tags($inner)), $attributes),
            'nextpage' => new PageBreak($attributes),
            'separator' => new Separator($attributes),
            'button' => $this->createButton($attributes, $inner),
            default => new $className($inner, $attributes),
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createQuote(array $attributes, string $inner): Quote
    {
        $withoutCite = preg_replace('/<cite>.*?<\/cite>/is', '', $inner) ?? $inner;

        return new Quote(
            trim(strip_tags($withoutCite)),
            $this->stringAttribute($attributes, 'citation'),
            $attributes,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createHeading(array $attributes, string $inner): Heading
    {
        $level = $attributes['level'] ?? 2;

        return new Heading(
            trim(strip_tags($inner)),
            is_int($level) && $level >= 1 && $level <= 6 ? $level : 2,
            $attributes,
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createImage(array $attributes, string $inner): Image
    {
        $src = '';
        if (preg_match('/<img src="([^"]*)"/', $inner, $match) === 1) {
            $src = html_entity_decode($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $alt = '';
        if (preg_match('/ alt="([^"]*)"/', $inner, $match) === 1) {
            $alt = html_entity_decode($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $id = $attributes['id'] ?? 0;

        return new Image(is_int($id) ? $id : 0, $src, $alt, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createReadMore(array $attributes, string $inner): ReadMore
    {
        $customText = '';
        if (preg_match('/<!--more\s+([^-->]*?)\s*-->/', $inner, $match) === 1) {
            $customText = trim($match[1]);
        }

        return new ReadMore($customText, $this->boolAttribute($attributes, 'noTeaser'), $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createButton(array $attributes, string $inner): Button
    {
        $url = '';
        if (preg_match('/href="([^"]*)"/', $inner, $match) === 1) {
            $url = html_entity_decode($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return new Button(trim(strip_tags($inner)), $url, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function stringAttribute(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function boolAttribute(array $attributes, string $key): bool
    {
        $value = $attributes[$key] ?? false;

        return is_bool($value) ? $value : (bool) $value;
    }
}
