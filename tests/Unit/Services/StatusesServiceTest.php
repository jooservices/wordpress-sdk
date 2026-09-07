<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Status;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class StatusesServiceTest extends TestCase
{
    public function testGetsBySlug(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['name' => $this->faker->word(), 'slug' => 'publish', 'public' => true]));
        $this->httpFakes()->respond('GET', '*wp/v2/statuses/publish*', $sequence);

        $status = $this->wordPress()->statuses()->get('publish');
        self::assertInstanceOf(Status::class, $status);
        self::assertSame('publish', $status->slug);
        self::assertTrue($status->public);
    }

    public function testListsAssociativePayload(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'publish' => ['name' => $this->faker->word(), 'public' => true],
            'draft' => ['name' => $this->faker->word()],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/statuses*', $sequence);

        $statuses = $this->wordPress()->statuses()->list();
        self::assertCount(2, $statuses);
        self::assertTrue($statuses->all()[0]->public);
    }
}
