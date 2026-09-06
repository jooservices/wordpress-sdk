<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Write\ApplicationPasswordPayload;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class ApplicationPasswordsServiceTest extends TestCase
{
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
}
