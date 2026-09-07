<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SettingsServiceTest extends TestCase
{
    public function testGetsAndUpdatesSettings(): void
    {
        $title = $this->faker->sentence(2);
        $updatedTitle = $this->faker->sentence(2);
        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['title' => $title, 'users_can_register' => 0]));
        $this->httpFakes()->respond('GET', '*wp/v2/settings*', $get);

        self::assertSame($title, $this->wordPress()->settings()->get()->get('title'));

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['title' => $updatedTitle]));
        $this->httpFakes()->respond('POST', '*wp/v2/settings*', $update);

        self::assertSame($updatedTitle, $this->wordPress()->settings()->update(['title' => $updatedTitle])->get('title'));
        $this->assertJsonBody($this->lastRequest(), ['title' => $updatedTitle]);
    }
}
