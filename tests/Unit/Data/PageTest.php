<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PageTest extends TestCase
{
    public function testHydratesHierarchicalFields(): void
    {
        $page = Page::from([
            'id' => '8',
            'type' => 'page',
            'parent' => 3,
            'menu_order' => 2,
            'title' => ['rendered' => $this->faker->sentence(2)],
        ]);

        self::assertSame(8, $page->id);
        self::assertSame(3, $page->parent);
        self::assertSame(2, $page->menu_order);
    }
}
