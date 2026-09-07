<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class BlockDirectoryServiceTest extends TestCase
{
    public function testSearchesDirectory(): void
    {
        $term = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['results' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/block-directory/search*', $sequence);

        self::assertSame(['results' => []], $this->wordPress()->blockDirectory()->search(['term' => $term]));
        $this->assertQuery($this->lastRequest(), ['term' => $term]);
    }
}
