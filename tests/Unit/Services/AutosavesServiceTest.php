<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use InvalidArgumentException;
use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class AutosavesServiceTest extends TestCase
{
    public function testCreatesResourcesForCorePostTypes(): void
    {
        $autosaves = $this->wordPress()->autosaves();

        foreach (['posts' => 'posts', 'pages' => 'pages', 'blocks' => 'blocks'] as $method => $path) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json([['id' => 1]]));
            $this->httpFakes()->respond('GET', '*wp/v2/' . $path . '/1/autosaves*', $sequence);

            self::assertSame([['id' => 1]], $autosaves->{$method}(1)->list());
        }
    }

    public function testListsGetsCreatesAndValidatesResources(): void
    {
        $resource = $this->wordPress()->autosaves()->resource('posts', 7);
        foreach ([
            ['GET', 'wp/v2/posts/7/autosaves', [['id' => 1]]],
            ['GET', 'wp/v2/posts/7/autosaves/1', ['id' => 1]],
            ['POST', 'wp/v2/posts/7/autosaves', ['id' => 2]],
        ] as [$method, $path, $body]) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json($body));
            $this->httpFakes()->respond($method, '*' . $path . '*', $sequence);
        }

        self::assertSame([['id' => 1]], $resource->list(['context' => 'edit']));
        self::assertSame(['id' => 1], $resource->get(1));
        self::assertSame(['id' => 2], $resource->create(['title' => $this->faker->sentence()]));

        $this->expectException(InvalidArgumentException::class);
        $this->wordPress()->autosaves()->resource('unknown', 1);
    }
}
