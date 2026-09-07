<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Column;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Columns;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ColumnsTest extends TestCase
{
    public function testWrapsColumnsAndInnerBlocks(): void
    {
        $content = $this->faker->word();
        $column = new Column();
        $column->addBlock(new Paragraph($content));
        $columns = new Columns();
        $columns->addBlock($column);

        self::assertStringContainsString("<p>{$content}</p>", $columns->render());
        self::assertSame([$column], $columns->getInnerBlocks());
    }
}
