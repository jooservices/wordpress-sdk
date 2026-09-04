<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support;

use InvalidArgumentException;
use JOOservices\WordPress\Sdk\Endpoints\Endpoint;
use JOOservices\WordPress\Sdk\Support\PostBackedResources;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class PostBackedResourcesTest extends TestCase
{
    public function testChildPathUsesEndpointAndEncodesParentId(): void
    {
        self::assertSame(
            'wp/v2/templates/theme%2Findex/revisions',
            PostBackedResources::childPath('templates', 'theme/index', 'revisions'),
        );
        self::assertSame(Endpoint::NAVIGATIONS, PostBackedResources::endpoint('navigation'));
    }

    public function testUnknownResourceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PostBackedResources::assertSupported('comments', 'autosave');
    }
}
