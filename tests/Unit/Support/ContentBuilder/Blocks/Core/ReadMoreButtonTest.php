<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support\ContentBuilder\Blocks\Core;

use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMoreButton;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ReadMoreButtonTest extends TestCase
{
    public function testSelfClosesWithoutContent(): void
    {
        self::assertSame('<!-- wp:read-more /-->', (new ReadMoreButton())->render());
    }
}
