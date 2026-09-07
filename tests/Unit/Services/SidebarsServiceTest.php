<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SidebarsServiceTest extends TestCase
{
    public function testListsAndUpdatesSidebars(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['sidebars' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/sidebars*', $list);
        self::assertSame(['sidebars' => []], $this->wordPress()->sidebars()->list());

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['id' => 'sidebar-1']));
        $this->httpFakes()->respond('POST', '*wp/v2/sidebars/sidebar-1*', $update);
        self::assertSame(['id' => 'sidebar-1'], $this->wordPress()->sidebars()->update('sidebar-1', ['widgets' => []]));
    }
}
