<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Write\UserPayload;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class UsersServiceTest extends TestCase
{
    public function testMeReturnsAuthenticatedUser(): void
    {
        $name = $this->faker->name();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'name' => $name]));
        $this->httpFakes()->respond('GET', '*wp/v2/users/me*', $sequence);

        $user = $this->wordPress()->users()->me();

        self::assertSame(1, $user->id);
        self::assertSame($name, $user->name);
        self::assertSame('/wp-json/wp/v2/users/me', $this->lastRequest()->getUri()->getPath());
    }

    public function testCreatePostsJsonPayload(): void
    {
        $username = $this->faker->userName();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 7, 'username' => $username], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/users*', $sequence);

        $user = $this->wordPress()->users()->create(new UserPayload(
            username: $username,
            email: $this->faker->email(),
        ));

        self::assertSame(7, $user->id);
        self::assertSame('/wp-json/wp/v2/users', $this->lastRequest()->getUri()->getPath());
    }

    public function testUpdateMeUsesUsersMePath(): void
    {
        $description = $this->faker->sentence();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'description' => $description]));
        $this->httpFakes()->respond('POST', '*wp/v2/users/me*', $sequence);

        $user = $this->wordPress()->users()->updateMe(['description' => $description]);

        self::assertSame($description, $user->description);
        self::assertSame('/wp-json/wp/v2/users/me', $this->lastRequest()->getUri()->getPath());
        $this->assertJsonBody($this->lastRequest(), ['description' => $description]);
    }

    public function testDeleteMeSendsForceAndReassign(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['deleted' => true, 'previous' => ['id' => 1, 'name' => 'Admin']]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/users/me*', $sequence);

        $deleted = $this->wordPress()->users()->deleteMe(force: true, reassign: 2);

        self::assertSame(1, $deleted->id);
        $this->assertQuery($this->lastRequest(), ['force' => 'true', 'reassign' => 2]);
    }
}
