<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\User;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class UserTest extends TestCase
{
    public function testHydrates(): void
    {
        $name = $this->faker->name();
        $avatarUrl = $this->faker->imageUrl();
        $username = $this->faker->userName();
        $user = User::from([
            'id' => '9',
            'name' => $name,
            'slug' => $this->faker->slug(),
            'link' => $this->faker->url(),
            'avatar_urls' => ['96' => $avatarUrl],
            'meta' => ['nickname' => $name],
            'username' => $username,
        ]);

        self::assertSame(9, $user->id);
        self::assertSame($name, $user->name);
        self::assertSame($username, $user->username);
        self::assertSame(['96' => $avatarUrl], $user->avatar_urls);
    }

    public function testHydratesEditContextFields(): void
    {
        $email = $this->faker->email();
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        $user = User::from([
            'id' => 2,
            'name' => $this->faker->name(),
            'slug' => $this->faker->slug(),
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'nickname' => $this->faker->userName(),
            'locale' => 'en_US',
            'registered_date' => '2026-01-01T00:00:00',
            'roles' => ['editor'],
            'capabilities' => ['edit_posts' => true],
            'extra_capabilities' => ['administrator' => false],
        ]);

        self::assertSame($email, $user->email);
        self::assertSame($firstName, $user->first_name);
        self::assertSame($lastName, $user->last_name);
        self::assertSame(['editor'], $user->roles);
        self::assertSame(['edit_posts' => true], $user->capabilities);
        self::assertSame(['administrator' => false], $user->extra_capabilities);
    }

    public function testHydratesWithoutUsername(): void
    {
        self::assertNull(User::from(['id' => 1, 'slug' => $this->faker->slug()])->username);
    }
}
