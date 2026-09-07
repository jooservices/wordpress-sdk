<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SearchResultTest extends TestCase
{
    public function testHydratesWordPressPayload(): void
    {
        $title = $this->faker->sentence(2);
        $result = SearchResult::from([
            'id' => '12', 'title' => $title, 'url' => $this->faker->url(),
            'type' => 'post', 'subtype' => 'post',
        ]);

        self::assertSame(12, $result->id);
        self::assertSame($title, $result->title);
        self::assertSame('post', $result->type);
    }

    public function testCastsNumericStringIdentifierToInteger(): void
    {
        $id = $this->faker->numberBetween(1);
        $result = SearchResult::from([
            'id' => (string) $id,
            'title' => $this->faker->sentence(2),
            'url' => $this->faker->url(),
            'type' => $this->faker->word(),
            'subtype' => $this->faker->word(),
        ]);

        self::assertSame($id, $result->id);
    }

    public function testPreservesStringIdentifier(): void
    {
        $id = $this->faker->slug();
        $result = SearchResult::from([
            'id' => $id,
            'title' => $this->faker->sentence(2),
            'url' => $this->faker->url(),
            'type' => $this->faker->word(),
            'subtype' => $this->faker->word(),
        ]);

        self::assertSame($id, $result->id);
    }
}
