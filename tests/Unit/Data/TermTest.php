<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Term;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class TermTest extends TestCase
{
    public function testHydrates(): void
    {
        $name = $this->faker->word();
        $term = Term::from([
            'id' => '5', 'count' => '3', 'description' => $this->faker->sentence(),
            'name' => $name, 'slug' => $this->faker->slug(), 'taxonomy' => 'category', 'parent' => '0',
        ]);

        self::assertSame(5, $term->id);
        self::assertSame(3, $term->count);
        self::assertSame($name, $term->name);
        self::assertSame('category', $term->taxonomy);
    }

    public function testPreservesEmbeddedRestMetadata(): void
    {
        $links = ['self' => [['href' => $this->faker->url()]]];
        $embedded = ['wp:post_type' => [['id' => $this->faker->numberBetween(1)]]];
        $term = Term::from(['_links' => $links, '_embedded' => $embedded]);

        self::assertSame($links, $term->_links);
        self::assertSame($embedded, $term->_embedded);
    }
}
