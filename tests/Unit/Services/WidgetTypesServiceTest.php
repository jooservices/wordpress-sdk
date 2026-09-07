<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class WidgetTypesServiceTest extends TestCase
{
    public function testListsAndEncodesWidgetTypes(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json(['types' => []]));
        $this->httpFakes()->respond('GET', '*wp/v2/widget-types*', $list);
        self::assertSame(['types' => []], $this->wordPress()->widgetTypes()->list());

        $encoded = $this->faker->word();
        $encode = new TestResponseSequence();
        $encode->push(TestResponse::json(['encoded' => $encoded]));
        $this->httpFakes()->respond('POST', '*wp/v2/widget-types/paragraph/encode*', $encode);
        self::assertSame(
            ['encoded' => $encoded],
            $this->wordPress()->widgetTypes()->encode('paragraph', ['text' => $this->faker->sentence()]),
        );

        $preview = sprintf('<p>%s</p>', $this->faker->sentence());
        $render = new TestResponseSequence();
        $render->push(TestResponse::json(['preview' => $preview]));
        $this->httpFakes()->respond('POST', '*wp/v2/widget-types/text/render*', $render);
        self::assertSame(['preview' => $preview], $this->wordPress()->widgetTypes()->render('text', ['text' => $this->faker->sentence()]));
    }
}
