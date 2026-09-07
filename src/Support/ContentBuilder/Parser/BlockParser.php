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
        return $this->parseRange($content, $registry, 0, strlen($content), $this->indexClosers($content));
    }

    /**
     * @param array<int, array{start: int, end: int}> $closers
     *
     * @return list<BlockInterface>
     */
    private function parseRange(
        string $content,
        BlockRegistry $registry,
        int $start,
        int $end,
        array $closers,
    ): array {
        $blocks = [];
        $offset = $start;

        while ($offset < $end) {
            $headerStart = strpos($content, '<!-- wp:', $offset);

            if ($headerStart === false || $headerStart >= $end) {
                $this->appendText($blocks, substr($content, $offset, $end - $offset));

                break;
            }

            $this->appendText($blocks, substr($content, $offset, $headerStart - $offset));
            $opening = $this->openingAt($content, $headerStart, $end);
            if ($opening === null) {
                $this->appendText($blocks, substr($content, $headerStart, $end - $headerStart));

                break;
            }

            if ($opening['self_closing']) {
                $blocks[] = $this->createBlock(
                    $opening['name'],
                    $opening['attributes'],
                    $content,
                    $opening['end'],
                    $opening['end'],
                    $registry,
                    $closers,
                );

                $offset = $opening['end'];

                continue;
            }

            $closer = $closers[$headerStart] ?? null;

            if ($closer === null || $closer['start'] >= $end) {
                $this->appendText($blocks, substr($content, $headerStart, $end - $headerStart));

                break;
            }

            $blocks[] = $this->createBlock(
                $opening['name'],
                $opening['attributes'],
                $content,
                $opening['end'],
                $closer['start'],
                $registry,
                $closers,
            );

            $offset = $closer['end'];
        }

        return $blocks;
    }

    /**
     * @return array{name: string, attributes: array<string, mixed>, end: int, self_closing: bool}|null
     */
    private function openingAt(string $content, int $start, int $rangeEnd): ?array
    {
        $headerEnd = strpos($content, '-->', $start);
        if ($headerEnd === false || $headerEnd >= $rangeEnd) {
            return null;
        }

        $header = substr($content, $start + 8, $headerEnd - $start - 8);
        $name = $this->blockName($header);
        if ($name === null) {
            return null;
        }

        $selfClosing = str_ends_with($header, '/');
        $attributesJson = trim(substr($header, strlen($name)));
        if ($selfClosing) {
            $attributesJson = rtrim(substr($attributesJson, 0, -1));
        }

        return [
            'name' => $name,
            'attributes' => $this->decodeAttributes($attributesJson),
            'end' => $headerEnd + 3,
            'self_closing' => $selfClosing,
        ];
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

    private function blockName(string $header): ?string
    {
        $nameLength = strcspn($header, " \t\r\n");
        $name = substr($header, 0, $nameLength);

        return $name === '' ? null : $name;
    }

    /**
     * Index each matching closing comment once so nested parsing does not
     * repeatedly rescan the same content.
     *
     * @return array<int, array{start: int, end: int}>
     */
    private function indexClosers(string $content): array
    {
        preg_match_all(
            '/<!-- (\/?)wp:([^\s]+)(.*?)-->/s',
            $content,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        /** @var array<string, list<int>> $openers */
        $openers = [];
        $closers = [];

        foreach ($matches as $match) {
            $comment = $match[0][0];
            $position = $match[0][1];
            $closing = $match[1][0] === '/';
            $name = $match[2][0];

            if (! $closing && ! str_ends_with(substr($comment, 0, -3), '/')) {
                $openers[$name][] = $position;

                continue;
            }

            if (! $closing || ($openers[$name] ?? []) === []) {
                continue;
            }

            $opener = array_pop($openers[$name]);
            if ($opener !== null) {
                $closers[$opener] = [
                    'start' => $position,
                    'end' => $position + strlen($comment),
                ];
            }
        }

        return $closers;
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
     * @param array<int, array{start: int, end: int}> $closers
     */
    private function createBlock(
        string $name,
        array $attributes,
        string $content,
        int $innerStart,
        int $innerEnd,
        BlockRegistry $registry,
        array $closers,
    ): BlockInterface {
        $className = $this->resolveClass($name, $registry);

        if ($className === null) {
            return new GenericBlock($name, $attributes, substr($content, $innerStart, $innerEnd - $innerStart));
        }

        if (is_subclass_of($className, ContainerBlock::class)) {
            return $this->createContainer(
                $className,
                $attributes,
                $this->parseRange($content, $registry, $innerStart, $innerEnd, $closers),
            );
        }

        return $this->createLeaf(
            $className,
            $attributes,
            substr($content, $innerStart, $innerEnd - $innerStart),
        );
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
     * @param list<BlockInterface> $children
     */
    private function createContainer(string $className, array $attributes, array $children): BlockInterface
    {
        if ($className === Group::class) {
            $tagName = $attributes['tagName'] ?? 'div';
            $container = new Group(is_string($tagName) ? $tagName : 'div', $attributes);
        } else {
            /** @var ContainerBlock $container */
            $container = new $className($attributes);
        }

        foreach ($children as $child) {
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
    private function createLeaf(string $className, array $attributes, string $inner): BlockInterface
    {
        return match ($className) {
            Paragraph::class => new Paragraph($this->unwrapTag($inner, 'p'), $attributes, escapeText: false),
            Heading::class => $this->createHeading($attributes, $inner),
            Image::class => $this->createImage($attributes, $inner),
            Quote::class => $this->createQuote($attributes, $inner),
            Code::class => new Code($this->unwrapCode($inner), $attributes),
            Shortcode::class => new Shortcode(trim($inner), $attributes),
            HtmlBlock::class => new HtmlBlock($inner, $attributes),
            ReadMore::class => $this->createReadMore($attributes, $inner),
            ReadMoreButton::class => new ReadMoreButton($this->anchorInnerHtml($inner), $attributes),
            PageBreak::class => new PageBreak($attributes),
            Separator::class => new Separator($attributes),
            Button::class => $this->createButton($attributes, $inner),
            default => new $className($inner, $attributes),
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createQuote(array $attributes, string $inner): Quote
    {
        $withoutCite = preg_replace('/<cite>.*?<\/cite>/is', '', $inner) ?? $inner;
        $body = $this->unwrapTag($withoutCite, 'blockquote');
        $body = preg_replace('#</p>\s*<p\b[^>]*>#i', "\n\n", $body) ?? $body;
        $body = $this->unwrapTag($body, 'p');

        return new Quote(
            trim($body),
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
        $resolvedLevel = is_int($level) && $level >= 1 && $level <= 6 ? $level : 2;

        return new Heading(
            $this->unwrapHeading($inner, $resolvedLevel),
            $resolvedLevel,
            $attributes,
            escapeText: false,
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

        return new Button($this->anchorInnerHtml($inner), $url, $attributes, escapeText: false);
    }

    /**
     * Keep inline markup; only unwrap a single outer HTML tag when present.
     */
    private function unwrapTag(string $html, string $tag): string
    {
        $trimmed = trim($html);
        $quoted = preg_quote($tag, '#');
        if (preg_match('#^<' . $quoted . '\b[^>]*>(.*)</' . $quoted . '>\s*$#is', $trimmed, $match) === 1) {
            return trim($match[1]);
        }

        return $trimmed;
    }

    private function unwrapHeading(string $html, int $level): string
    {
        $trimmed = trim($html);
        if (preg_match('#^<h([1-6])\b[^>]*>(.*)</h\1>\s*$#is', $trimmed, $match) === 1) {
            return trim($match[2]);
        }

        return $this->unwrapTag($html, 'h' . $level);
    }

    private function unwrapCode(string $html): string
    {
        $trimmed = trim($html);
        if (preg_match('#<code\b[^>]*>(.*)</code>#is', $trimmed, $match) === 1) {
            return html_entity_decode(trim($match[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return html_entity_decode($trimmed, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function anchorInnerHtml(string $html): string
    {
        if (preg_match('#<a\b[^>]*>(.*?)</a>#is', $html, $match) === 1) {
            return trim($match[1]);
        }

        return trim($html);
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
