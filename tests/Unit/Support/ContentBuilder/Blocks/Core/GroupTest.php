<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Group;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class GroupTest extends TestCase
{
    public function testUsesTagName(): void
    {
        $content = $this->faker->sentence();
        $group = new Group('section');
        $group->addBlock(new Paragraph($content));

        self::assertStringContainsString('<section class="wp-block-group">', $group->render());
        self::assertStringContainsString("<p>{$content}</p>", $group->render());
    }

    public function testRejectsUnsupportedTagName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Group('script');
    }
}
