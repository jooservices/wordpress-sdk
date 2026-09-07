<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\PostType;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PostTypesServiceTest extends TestCase
{
    public function testGetsBySlug(): void
    {
        $name = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['slug' => 'post', 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/types/post*', $sequence);

        $postType = $this->wordPress()->postTypes()->get('post');
        self::assertInstanceOf(PostType::class, $postType);
        self::assertSame($name, $postType->name);
    }

    public function testListsAssociativePayloadAndStreamsItems(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'post' => ['slug' => 'post', 'name' => $this->faker->word()],
            'page' => ['slug' => 'page', 'name' => $this->faker->word()],
        ], JSON_THROW_ON_ERROR)));
        $sequence->push(TestResponse::make(200, [], json_encode([
            'post' => ['slug' => 'post', 'name' => $this->faker->word()],
            'page' => ['slug' => 'page', 'name' => $this->faker->word()],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/types*', $sequence);

        self::assertCount(2, $this->wordPress()->postTypes()->list());
        $ids = [];
        $this->wordPress()->postTypes()->each(static function (PostType $type) use (&$ids): void {
            $ids[] = $type->slug;
        });
        self::assertSame(['post', 'page'], $ids);
    }
}
