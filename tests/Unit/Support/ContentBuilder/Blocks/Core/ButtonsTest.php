<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Buttons;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ButtonsTest extends TestCase
{
    public function testWrapsButtons(): void
    {
        $text = $this->faker->word();
        $url = $this->faker->url();
        $buttons = new Buttons();
        $buttons->addBlock(new Button($text, $url));

        self::assertStringContainsString('<!-- wp:buttons -->', $buttons->render());
        self::assertStringContainsString("href=\"{$url}\">{$text}</a>", $buttons->render());
    }
}
