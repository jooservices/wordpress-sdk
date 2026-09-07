<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\BlockRegistry;
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
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class BlockRegistryTest extends TestCase
{
    public function testRegistersCoreBlocksByDefault(): void
    {
        $registry = new BlockRegistry();
        $expected = [
            'core/paragraph' => Paragraph::class, 'core/heading' => Heading::class,
            'core/image' => Image::class, 'core/quote' => Quote::class, 'core/more' => ReadMore::class,
            'core/read-more' => ReadMoreButton::class, 'core/nextpage' => PageBreak::class,
            'core/separator' => Separator::class, 'core/code' => Code::class,
            'core/shortcode' => Shortcode::class, 'core/html' => HtmlBlock::class,
            'core/button' => Button::class, 'core/buttons' => Buttons::class,
            'core/column' => Column::class, 'core/columns' => Columns::class, 'core/group' => Group::class,
        ];

        foreach ($expected as $name => $class) {
            self::assertSame($class, $registry->get($name));
        }
    }

    public function testRegistersAndUnregistersBlocks(): void
    {
        $registry = new BlockRegistry();
        $registry->register('my-plugin/block', GenericBlock::class);
        self::assertTrue($registry->has('my-plugin/block'));
        self::assertCount(17, $registry->all());
        $registry->unregister('my-plugin/block');
        self::assertFalse($registry->has('my-plugin/block'));
    }

    public function testRejectsNonBlockClasses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new BlockRegistry())->register('core/x', 'NonExistent\\BlockClass');
    }

    public function testRejectsUnknownBlocks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new BlockRegistry())->get('core/unknown');
    }
}
