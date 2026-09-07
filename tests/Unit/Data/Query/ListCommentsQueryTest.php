<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data\Query;

use JOOservices\WordPress\Sdk\Data\Query\ListCommentsQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ListCommentsQueryTest extends TestCase
{
    public function testMapsCommentParameters(): void
    {
        self::assertSame(
            ['post' => 42, 'parent' => 1, 'status' => 'approve', 'type' => 'comment'],
            (new ListCommentsQuery(post: 42, parent: 1, status: 'approve', type: 'comment'))->toQuery(),
        );
    }
}
