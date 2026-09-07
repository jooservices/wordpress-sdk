<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Taxonomy;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class TaxonomyTest extends TestCase
{
    public function testHydrates(): void
    {
        $name = $this->faker->word();
        $taxonomy = Taxonomy::from([
            'slug' => 'category', 'name' => $name, 'types' => ['post', 'page'],
            'rest_base' => 'categories', 'hierarchical' => true, 'rest_namespace' => 'wp/v2',
            'labels' => ['name' => $name],
        ]);

        self::assertSame('category', $taxonomy->slug);
        self::assertSame(['post', 'page'], $taxonomy->types);
        self::assertTrue($taxonomy->hierarchical);
    }
}
