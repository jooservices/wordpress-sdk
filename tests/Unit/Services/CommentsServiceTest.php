<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class CommentsServiceTest extends TestCase
{
    public function testRoutesToCommentsEndpoint(): void
    {
        $name = $this->faker->name();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'author_name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/comments/1*', $sequence);

        self::assertSame($name, $this->wordPress()->comments()->get(1)->author_name);
        self::assertSame('/wp-json/wp/v2/comments/1', $this->lastRequest()->getUri()->getPath());
    }
}
