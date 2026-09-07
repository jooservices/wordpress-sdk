<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\ApplicationPassword;
use JOOservices\WordPress\Sdk\Data\Write\ApplicationPasswordPayload;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ApplicationPasswordsServiceTest extends TestCase
{
    public function testListsCreatesAndDeletesPasswords(): void
    {
        $uuid = $this->faker->uuid();
        $name = $this->faker->word();
        $list = new TestResponseSequence();
        $list->push(TestResponse::make(200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1'], json_encode([
            ['uuid' => $uuid, 'name' => $name],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/users/me/application-passwords*', $list);

        $passwords = $this->wordPress()->applicationPasswords()->list('me');
        self::assertInstanceOf(ApplicationPassword::class, $passwords->all()[0]);

        $secret = $this->faker->password();
        $create = new TestResponseSequence();
        $create->push(TestResponse::json(['uuid' => $uuid, 'name' => $name, 'password' => $secret], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/users/me/application-passwords*', $create);
        self::assertSame($secret, $this->wordPress()->applicationPasswords()->create('me', ['name' => $name])->password);

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/users/me/application-passwords/' . $uuid . '*', $delete);
        self::assertSame(['deleted' => true], $this->wordPress()->applicationPasswords()->delete('me', $uuid));
    }

    public function testDeletesAllPasswords(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/users/5/application-passwords*', $sequence);

        self::assertSame(['deleted' => true], $this->wordPress()->applicationPasswords()->deleteAll(5));
        self::assertSame('/wp-json/wp/v2/users/5/application-passwords', $this->lastRequest()->getUri()->getPath());
    }

    public function testIntrospectsCurrentPassword(): void
    {
        $uuid = $this->faker->uuid();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['uuid' => $uuid, 'name' => $this->faker->word()]));
        $this->httpFakes()->respond('GET', '*wp/v2/users/me/application-passwords/introspect*', $sequence);

        self::assertSame($uuid, $this->wordPress()->applicationPasswords()->introspect()->uuid);
    }

    public function testUpdatePostsToUuidPath(): void
    {
        $name = $this->faker->sentence(2);
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['uuid' => 'abc', 'name' => $name]));
        $this->httpFakes()->respond('POST', '*wp/v2/users/me/application-passwords/abc*', $sequence);

        $updated = $this->wordPress()->applicationPasswords()->update(
            'me',
            'abc',
            new ApplicationPasswordPayload(name: $name),
        );

        self::assertSame($name, $updated->name);
        self::assertSame('/wp-json/wp/v2/users/me/application-passwords/abc', $this->lastRequest()->getUri()->getPath());
        $this->assertJsonBody($this->lastRequest(), ['name' => $name]);
    }

    public function testEncodesUserAndUuidPathSegments(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['uuid' => $this->faker->uuid()]));
        $this->httpFakes()->respond('GET', '*', $sequence);

        $this->wordPress()->applicationPasswords()->get('me/admin', 'x?context=edit');

        self::assertSame(
            '/wp-json/wp/v2/users/me%2Fadmin/application-passwords/x%3Fcontext%3Dedit',
            $this->lastRequest()->getUri()->getPath(),
        );
        self::assertSame('', $this->lastRequest()->getUri()->getQuery());
    }
}
