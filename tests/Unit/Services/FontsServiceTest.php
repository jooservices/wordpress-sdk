<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\Client\Request\MultipartStream;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class FontsServiceTest extends TestCase
{
    public function testManagesFamiliesFacesAndCollections(): void
    {
        $fonts = $this->wordPress()->fonts();
        foreach ([
            ['GET', 'wp/v2/font-families', 'families', [[]]],
            ['GET', 'wp/v2/font-families/1', 'family', [1]],
            ['POST', 'wp/v2/font-families', 'createFamily', [['name' => $this->faker->word()]]],
            ['GET', 'wp/v2/font-families/1/font-faces', 'faces', [1]],
            ['GET', 'wp/v2/font-families/1/font-faces/4', 'face', [1, 4]],
            ['GET', 'wp/v2/font-collections', 'collections', []],
            ['GET', 'wp/v2/font-collections/google-fonts', 'collection', ['google-fonts']],
        ] as [$verb, $path, $method, $arguments]) {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['ok' => true]));
            $this->httpFakes()->respond($verb, '*' . $path . '*', $sequence);
            self::assertSame(['ok' => true], $fonts->{$method}(...$arguments));
        }
    }

    public function testUpdatesAndDeletesFamiliesAndFaces(): void
    {
        $fonts = $this->wordPress()->fonts();
        $this->respond('POST', 'wp/v2/font-families/2', ['id' => 2]);
        self::assertSame(['id' => 2], $fonts->updateFamily(2, ['name' => $this->faker->word()]));
        $this->respond('DELETE', 'wp/v2/font-families/2', ['deleted' => true]);
        self::assertSame(['deleted' => true], $fonts->deleteFamily(2, false));

        $this->respond('POST', 'wp/v2/font-families/1/font-faces', ['id' => 5]);
        self::assertSame(['id' => 5], $fonts->createFace(1, ['font_weight' => '400']));
        $this->respond('POST', 'wp/v2/font-families/1/font-faces/5', ['id' => 5]);
        self::assertSame(['id' => 5], $fonts->updateFace(1, 5, ['font_style' => 'normal']));
        $this->respond('DELETE', 'wp/v2/font-families/1/font-faces/5', ['deleted' => true]);
        self::assertSame(['deleted' => true], $fonts->deleteFace(1, 5));
    }

    public function testUploadsFontFace(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sdk-font');
        self::assertNotFalse($file);
        file_put_contents($file, $this->faker->text());

        try {
            $this->respond('POST', 'wp/v2/font-families/1/font-faces', ['id' => 6]);
            self::assertSame(['id' => 6], $this->wordPress()->fonts()->uploadFace(1, $file, [
                'fontFamily' => $this->faker->word(), 'fontWeight' => '700', 'fontStyle' => 'normal',
            ]));
            self::assertInstanceOf(MultipartStream::class, $this->lastRequest()->getBody());
            self::assertStringStartsWith('multipart/form-data; boundary=', $this->lastRequest()->getHeaderLine('Content-Type'));
        } finally {
            unlink($file);
        }
    }

    /** @param array<mixed> $body */
    private function respond(string $method, string $path, array $body): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json($body));
        $this->httpFakes()->respond($method, '*' . $path . '*', $sequence);
    }
}
