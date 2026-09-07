<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Support\ContentBuilder;

use Closure;
use JOOservices\WordPress\Sdk\Services\MediaService;
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
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Parser\BlockParser;
use RuntimeException;

/**
 * Fluent Gutenberg block markup generator.
 *
 * Blocks accumulate on the builder and are serialized with
 * {@see render()} — compatible with the `content` field of the WordPress
 * REST API. An optional {@see MediaService} enables `imageFromFile()`.
 */
final class ContentBuilder
{
    /**
     * @var list<BlockInterface>
     */
    private array $blocks = [];

    private ?MediaService $mediaService = null;

    private ?BlockRegistry $resolvedRegistry = null;

    public function __construct(
        private readonly ?BlockRegistry $registry = null,
    ) {}

    public function setMediaService(MediaService $mediaService): self
    {
        $this->mediaService = $mediaService;

        return $this;
    }

    public function registry(): BlockRegistry
    {
        return $this->resolvedRegistry ??= $this->registry ?? new BlockRegistry();
    }

    /**
     * @return list<BlockInterface>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function addBlock(BlockInterface $block): self
    {
        $this->blocks[] = $block;

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function text(string $content, array $attributes = []): self
    {
        return $this->addBlock(new Paragraph($content, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function heading(string $text, int $level = 2, array $attributes = []): self
    {
        return $this->addBlock(new Heading($text, $level, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function image(int $mediaId, string $src = '', string $alt = '', array $attributes = []): self
    {
        return $this->addBlock(new Image($mediaId, $src, $alt, $attributes));
    }

    /**
     * Uploads a file through the configured MediaService and adds it as an
     * image block.
     *
     * @param array<string, mixed> $attributes
     */
    public function imageFromFile(string $filePath, array $attributes = []): self
    {
        if ($this->mediaService === null) {
            throw new RuntimeException('MediaService is not configured. Call setMediaService() first.');
        }

        $media = $this->mediaService->upload($filePath, $attributes);

        $alt = $attributes['alt_text'] ?? $media->alt_text;

        return $this->image($media->id, $media->source_url, is_scalar($alt) ? (string) $alt : '');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function quote(string $content, string $citation = '', array $attributes = []): self
    {
        return $this->addBlock(new Quote($content, $citation, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function readMore(string $customText = '', bool $noTeaser = false, array $attributes = []): self
    {
        return $this->addBlock(new ReadMore($customText, $noTeaser, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function readMoreButton(string $content = '', array $attributes = []): self
    {
        return $this->addBlock(new ReadMoreButton($content, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function pageBreak(array $attributes = []): self
    {
        return $this->addBlock(new PageBreak($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function separator(array $attributes = []): self
    {
        return $this->addBlock(new Separator($attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function code(string $code, array $attributes = []): self
    {
        return $this->addBlock(new Code($code, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function shortcode(string $shortcode, array $attributes = []): self
    {
        return $this->addBlock(new Shortcode($shortcode, $attributes));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function block(string $name, array $attributes = [], string $content = ''): self
    {
        return $this->addBlock(new GenericBlock($name, $attributes, $content));
    }

    public function html(string $rawHtml): self
    {
        return $this->addBlock(new HtmlBlock($rawHtml));
    }

    public static function fromHtml(string $html): self
    {
        return (new self())->addBlock(new HtmlBlock($html));
    }

    /**
     * Registers a block class for {@see parse()}.
     *
     * @param class-string<BlockInterface> $className
     */
    public function registerBlock(string $blockName, string $className): self
    {
        $this->registry()->register($blockName, $className);

        return $this;
    }

    public function render(): string
    {
        return implode("\n\n", array_map(
            static fn(BlockInterface $block): string => $block->render(),
            $this->blocks,
        ));
    }

    public function renderRaw(): string
    {
        return implode("\n\n", array_map(
            static fn(BlockInterface $block): string => $block->toHtml(),
            $this->blocks,
        ));
    }

    /**
     * Parses Gutenberg block markup into a new builder.
     */
    public static function parse(string $content, ?BlockRegistry $registry = null): self
    {
        $builder = new self($registry);
        $parser = new BlockParser();

        foreach ($parser->parse($content, $builder->registry()) as $block) {
            $builder->addBlock($block);
        }

        return $builder;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function button(string $text, string $url = '#', array $attributes = []): self
    {
        return $this->addBlock(new Button($text, $url, $attributes));
    }

    /**
     * @param list<Button|array{text: string, url?: string, attributes?: array<string, mixed>}> $buttons
     * @param array<string, mixed> $attributes
     */
    public function buttons(array $buttons, array $attributes = []): self
    {
        $container = new Buttons($attributes);

        foreach ($buttons as $button) {
            if ($button instanceof Button) {
                $container->addBlock($button);

                continue;
            }

            $container->addBlock(new Button(
                (string) ($button['text'] ?? ''),
                (string) ($button['url'] ?? '#'),
                isset($button['attributes']) && is_array($button['attributes']) ? $button['attributes'] : [],
            ));
        }

        return $this->addBlock($container);
    }

    /**
     * @param array<callable(ContentBuilder): void> $columnBuilders
     * @param array<string, mixed> $attributes
     */
    public function columns(array $columnBuilders, array $attributes = []): self
    {
        $container = new Columns($attributes);

        foreach ($columnBuilders as $columnBuilder) {
            $container->addBlock($this->buildColumn($columnBuilder));
        }

        return $this->addBlock($container);
    }

    /**
     * @param Closure(ContentBuilder): void $builderFunc
     * @param array<string, mixed> $attributes
     */
    public function group(Closure $builderFunc, array $attributes = []): self
    {
        $group = new Group('div', $attributes);

        foreach ($this->buildInner($builderFunc) as $block) {
            $group->addBlock($block);
        }

        return $this->addBlock($group);
    }

    /**
     * @param callable(ContentBuilder): void $columnBuilder
     */
    private function buildColumn(callable $columnBuilder): Column
    {
        $column = new Column();

        foreach ($this->buildInner($columnBuilder) as $block) {
            $column->addBlock($block);
        }

        return $column;
    }

    /**
     * @param callable(ContentBuilder): void $builderFunc
     *
     * @return list<BlockInterface>
     */
    private function buildInner(callable $builderFunc): array
    {
        $builder = new self($this->registry());
        if ($this->mediaService !== null) {
            $builder->setMediaService($this->mediaService);
        }

        $builderFunc($builder);

        return $builder->getBlocks();
    }
}
