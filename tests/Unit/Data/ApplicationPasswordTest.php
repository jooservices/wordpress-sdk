<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\ApplicationPassword;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ApplicationPasswordTest extends TestCase
{
    public function testHydrates(): void
    {
        $uuid = $this->faker->uuid();
        $name = $this->faker->word();
        $password = ApplicationPassword::from([
            'uuid' => $uuid, 'app_id' => 5, 'name' => $name,
            'created' => '2026-08-29T10:00:00', 'last_used' => '2026-08-29T11:00:00',
            'last_ip' => '127.0.0.1',
        ]);

        self::assertSame($uuid, $password->uuid);
        self::assertSame($name, $password->name);
        self::assertNull($password->password);
    }

    public function testCarriesGeneratedSecretOnCreate(): void
    {
        $secret = $this->faker->password();
        $password = ApplicationPassword::from([
            'uuid' => $this->faker->uuid(), 'name' => $this->faker->word(), 'password' => $secret,
        ]);

        self::assertSame($secret, $password->password);
    }
}
