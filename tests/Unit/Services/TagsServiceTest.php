<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class TagsServiceTest extends TestCase
{
    public function testRoutesToTagsEndpoint(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1'], json_encode([
            ['id' => 8, 'name' => $name, 'taxonomy' => 'post_tag'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/tags*', $sequence);

        $tags = $this->wordPress()->tags()->list();
        self::assertSame($name, $tags->all()[0]->name);
        self::assertSame('/wp-json/wp/v2/tags', $this->lastRequest()->getUri()->getPath());
    }
}
