<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class BlockTypesServiceTest extends TestCase
{
    public function testListsAndGetsNamespacedBlockTypes(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['blocks' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/block-types/core*', $list);
        self::assertSame(['blocks' => []], $this->wordPress()->blockTypes()->list('core'));

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['name' => 'core/paragraph']));
        $this->httpFakes()->respond('GET', '*wp/v2/block-types/core/paragraph*', $get);
        self::assertSame(['name' => 'core/paragraph'], $this->wordPress()->blockTypes()->get('core/paragraph'));
    }
}
