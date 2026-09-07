<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ThemesServiceTest extends TestCase
{
    public function testListsAndGetsThemes(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['themes' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/themes*', $list);
        self::assertSame(['themes' => []], $this->wordPress()->themes()->list());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['stylesheet' => 'twentytwentyfive']));
        $this->httpFakes()->respond('GET', '*wp/v2/themes/twentytwentyfive*', $get);
        self::assertSame(['stylesheet' => 'twentytwentyfive'], $this->wordPress()->themes()->get('twentytwentyfive'));
    }
}
