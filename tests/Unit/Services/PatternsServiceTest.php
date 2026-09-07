<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PatternsServiceTest extends TestCase
{
    public function testReadsPatternSourcesAndManagesTerms(): void
    {
        $patterns = $this->wordPress()->patterns();
        foreach ([
            ['patterns', 'wp/v2/block-patterns/patterns'],
            ['categories', 'wp/v2/block-patterns/categories'],
            ['directory', 'wp/v2/pattern-directory/patterns'],
            ['listTerms', 'wp/v2/wp_pattern_category'],
        ] as [$method, $path]) {
            $this->respond('GET', $path, ['ok' => true]);
            self::assertSame(['ok' => true], $patterns->{$method}([]));
        }

        $this->respond('GET', 'wp/v2/wp_pattern_category/2', ['id' => 2]);
        self::assertSame(['id' => 2], $patterns->getTerm(2));
        $this->respond('POST', 'wp/v2/wp_pattern_category', ['id' => 3]);
        self::assertSame(['id' => 3], $patterns->createTerm(['name' => $this->faker->word()]));
        $this->respond('POST', 'wp/v2/wp_pattern_category/3', ['id' => 3]);
        self::assertSame(['id' => 3], $patterns->updateTerm(3, ['name' => $this->faker->word()]));
        $this->respond('DELETE', 'wp/v2/wp_pattern_category/3', ['deleted' => true]);
        self::assertSame(['deleted' => true], $patterns->deleteTerm(3, false));
    }

    /** @param array<mixed> $body */
    private function respond(string $method, string $path, array $body): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json($body));
        $this->httpFakes()->respond($method, '*' . $path . '*', $sequence);
    }
}
