<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class GlobalStylesServiceTest extends TestCase
{
    public function testListsReadsThemeAndUpdatesStyles(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['styles' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/global-styles*', $list);
        self::assertSame(['styles' => []], $this->wordPress()->globalStyles()->list());

        $theme = new TestResponseSequence();
        $theme->push(TestResponse::json(['settings' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/global-styles/themes/theme*', $theme);
        self::assertSame(['settings' => []], $this->wordPress()->globalStyles()->theme('theme'));

        $variations = new TestResponseSequence();
        $variations->push(TestResponse::json(['items' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/global-styles/themes/theme/variations*', $variations);
        self::assertSame(['items' => []], $this->wordPress()->globalStyles()->variations('theme'));

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['id' => 1]));
        $this->httpFakes()->respond('POST', '*wp/v2/global-styles/1*', $update);
        self::assertSame(['id' => 1], $this->wordPress()->globalStyles()->update(1, ['settings' => []]));
    }

    public function testEncodesStringIdAsOnePathSegment(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => $this->faker->uuid()]));
        $this->httpFakes()->respond('GET', '*', $sequence);

        $this->wordPress()->globalStyles()->get('x/y?context=edit');

        self::assertSame(
            '/wp-json/wp/v2/global-styles/x%2Fy%3Fcontext%3Dedit',
            $this->lastRequest()->getUri()->getPath(),
        );
        self::assertSame('', $this->lastRequest()->getUri()->getQuery());
    }
}
