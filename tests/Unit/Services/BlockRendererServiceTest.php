<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class BlockRendererServiceTest extends TestCase
{
    public function testSendsEditContext(): void
    {
        $html = sprintf('<div>%s</div>', $this->faker->sentence());
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['rendered' => $html]));
        $this->httpFakes()->respond('GET', '*wp/v2/block-renderer/core/latest-posts*', $sequence);

        self::assertSame(['rendered' => $html], $this->wordPress()->blockRenderer()->render('core/latest-posts', ['postsToShow' => 3], 12));
        $this->assertQuery($this->lastRequest(), ['context' => 'edit', 'post_id' => 12]);
    }
}
