<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class RawCrudTest extends TestCase
{
    public function testProvidesCrudSurfaceToRawServices(): void
    {
        foreach ([
            'blocks' => ['blocks', 'wp/v2/blocks'],
            'navigations' => ['navigations', 'wp/v2/navigation'],
            'navMenus' => ['navMenus', 'wp/v2/menus'],
            'navMenuItems' => ['navMenuItems', 'wp/v2/menu-items'],
            'templates' => ['templates', 'wp/v2/templates'],
            'templateParts' => ['templateParts', 'wp/v2/template-parts'],
            'widgets' => ['widgets', 'wp/v2/widgets'],
        ] as $label => [$accessor, $path]) {
            $service = $this->wordPress()->{$accessor}();
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['items' => [1]]));
            $this->httpFakes()->respond('GET', '*' . $path . '*', $sequence);
            self::assertSame(['items' => [1]], $service->list(['per_page' => 5]), $label);

            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['id' => 1]));
            $this->httpFakes()->respond('GET', '*' . $path . '/1*', $sequence);
            self::assertSame(['id' => 1], $service->get(1), $label);

            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['id' => 2], 201));
            $this->httpFakes()->respond('POST', '*' . $path . '*', $sequence);
            self::assertSame(['id' => 2], $service->create(['name' => $this->faker->word()]), $label);

            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['id' => 2]));
            $this->httpFakes()->respond('POST', '*' . $path . '/2*', $sequence);
            self::assertSame(['id' => 2], $service->update(2, ['name' => $this->faker->word()]), $label);

            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['deleted' => true]));
            $this->httpFakes()->respond('DELETE', '*' . $path . '/2*', $sequence);
            self::assertSame(['deleted' => true], $service->delete(2, force: true), $label);
        }
    }
}
