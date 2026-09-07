<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class MenuLocationsServiceTest extends TestCase
{
    public function testListsAndGetsLocations(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['locations' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/menu-locations*', $list);
        self::assertSame(['locations' => []], $this->wordPress()->menuLocations()->list());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['location' => 'primary']));
        $this->httpFakes()->respond('GET', '*wp/v2/menu-locations/primary*', $get);
        self::assertSame(['location' => 'primary'], $this->wordPress()->menuLocations()->get('primary'));
    }
}
