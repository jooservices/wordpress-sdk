<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Settings;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class SettingsTest extends TestCase
{
    public function testWrapsValuesAndLooksUpKeys(): void
    {
        $title = $this->faker->sentence(2);
        $fallback = $this->faker->word();
        $settings = Settings::from(['title' => $title, 'users_can_register' => 0]);

        self::assertSame($title, $settings->get('title'));
        self::assertSame(0, $settings->get('users_can_register'));
        self::assertSame($fallback, $settings->get('missing', $fallback));
        self::assertNull($settings->get('missing'));
    }

    public function testPreservesArrayValuedSettingNamedValues(): void
    {
        $title = $this->faker->sentence(2);
        $settings = Settings::from(['values' => ['title' => $title]]);

        self::assertSame(['title' => $title], $settings->get('values'));
        self::assertSame(['values' => ['title' => $title]], $settings->toArray());
    }
}
