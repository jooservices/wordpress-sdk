<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Status;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class StatusTest extends TestCase
{
    public function testHydrates(): void
    {
        $name = $this->faker->word();
        $status = Status::from([
            'name' => $name, 'slug' => 'publish', 'public' => true,
            'queryable' => true, 'show_in_list' => true,
        ]);

        self::assertSame($name, $status->name);
        self::assertSame('publish', $status->slug);
        self::assertTrue($status->public);
        self::assertFalse($status->protected);
    }
}
