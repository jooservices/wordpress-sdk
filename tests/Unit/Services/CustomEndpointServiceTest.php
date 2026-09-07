<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use InvalidArgumentException;
use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class CustomEndpointServiceTest extends TestCase
{
    public function testSupportsVerbsAndNormalizesPaths(): void
    {
        $name = $this->faker->word();
        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['items' => []]));
        $this->httpFakes()->respond('GET', '*my-plugin/v1/items*', $get);

        self::assertSame(['items' => []], $this->wordPress()->custom()->get('my-plugin//v1/items/', ['page' => 1]));
        self::assertSame('/wp-json/my-plugin/v1/items', $this->lastRequest()->getUri()->getPath());
        $this->assertQuery($this->lastRequest(), ['page' => 1]);

        $post = new TestResponseSequence();
        $post->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*my-plugin/v1/items*', $post);
        self::assertSame(['id' => 1], $this->wordPress()->custom()->post('my-plugin/v1/items', ['name' => $name]));
        $this->assertJsonBody($this->lastRequest(), ['name' => $name]);

        $updatedName = $this->faker->word();
        $patch = new TestResponseSequence();
        $patch->push(TestResponse::json(['id' => 1, 'name' => $updatedName]));
        $this->httpFakes()->respond('PATCH', '*my-plugin/v1/items*', $patch);
        self::assertSame(
            ['id' => 1, 'name' => $updatedName],
            $this->wordPress()->custom()->patch('my-plugin/v1/items/1', ['name' => $updatedName]),
        );
        self::assertSame('PATCH', $this->lastRequest()->getMethod());

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*my-plugin/v1/items/1*', $delete);
        self::assertSame(['deleted' => true], $this->wordPress()->custom()->delete('my-plugin/v1/items/1'));
    }

    public function testRejectsAbsoluteUrls(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->wordPress()->custom()->get('https://evil.example.com/api');
    }
}
