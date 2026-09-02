<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Buttons;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Code;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Column;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Columns;
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
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;
use InvalidArgumentException;

/**
 * Per-builder registry of block classes, keyed by full block name
 * (`core/paragraph`, `my-plugin/foo`).
 */
final class BlockRegistry
{
    /**
     * @var array<string, class-string<BlockInterface>>
     */
    private array $blocks;

    public function __construct()
    {
        $this->blocks = [
            'core/paragraph' => Paragraph::class,
            'core/heading' => Heading::class,
            'core/image' => Image::class,
            'core/quote' => Quote::class,
            'core/more' => ReadMore::class,
            'core/read-more' => ReadMoreButton::class,
            'core/nextpage' => PageBreak::class,
            'core/separator' => Separator::class,
            'core/code' => Code::class,
            'core/shortcode' => Shortcode::class,
            'core/html' => HtmlBlock::class,
            'core/button' => Button::class,
            'core/buttons' => Buttons::class,
            'core/column' => Column::class,
            'core/columns' => Columns::class,
            'core/group' => Group::class,
        ];
    }

    /**
     * The class name is validated at runtime: it must implement
     * {@see BlockInterface}.
     */
    public function register(string $blockName, string $className): void
    {
        if (! is_subclass_of($className, BlockInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                'Block class %s must implement %s.',
                $className,
                BlockInterface::class,
            ));
        }

        $this->blocks[$blockName] = $className;
    }

    public function unregister(string $blockName): void
    {
        unset($this->blocks[$blockName]);
    }

    public function has(string $blockName): bool
    {
        return isset($this->blocks[$blockName]);
    }

    /**
     * @return class-string<BlockInterface>
     */
    public function get(string $blockName): string
    {
        if (! isset($this->blocks[$blockName])) {
            throw new InvalidArgumentException(sprintf("Block '%s' is not registered.", $blockName));
        }

        return $this->blocks[$blockName];
    }

    /**
     * @return array<string, class-string<BlockInterface>>
     */
    public function all(): array
    {
        return $this->blocks;
    }
}
