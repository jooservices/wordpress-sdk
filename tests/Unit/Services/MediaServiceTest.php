<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Media;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class MediaServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testListReturnsTypedMedia(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 3, 'mime_type' => 'image/png', 'source_url' => 'https://example.com/a.png'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/media*', $sequence);

        $media = $this->wordPress->media()->list();

        self::assertInstanceOf(Media::class, $media->all()[0]);
        self::assertSame('image/png', $media->all()[0]->mime_type);
    }

    public function testUploadSendsMultipartRequest(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sdk-upload');
        file_put_contents($file, 'binary-data');

        try {
            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::json(['id' => 11, 'source_url' => 'https://example.com/u.png'], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/media*', $sequence);

            $media = $this->wordPress->media()->upload($file, ['title' => 'Uploaded']);

            self::assertSame(11, $media->id);
            $request = $this->lastRequest();
            self::assertSame('POST', $request->getMethod());
            self::assertSame('/wp-json/wp/v2/media', $request->getUri()->getPath());
            self::assertInstanceOf(\JOOservices\Client\Request\MultipartStream::class, $request->getBody());
            self::assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderLine('Content-Type'));
        } finally {
            unlink($file);
        }
    }

    public function testUploadRejectsMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->wordPress->media()->upload('/nonexistent/file.png');
    }

    public function testUpdateIsPublicOnMediaService(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 3, 'alt_text' => 'New alt']));
        $this->httpFakes()->respond('POST', '*wp/v2/media/3*', $sequence);

        $media = $this->wordPress->media()->update(3, ['alt_text' => 'New alt']);

        self::assertSame('New alt', $media->alt_text);
        self::assertSame('/wp-json/wp/v2/media/3', $this->lastRequest()->getUri()->getPath());
    }

    public function testCreateRejectsJsonPost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('multipart upload');

        $this->wordPress->media()->create(['title' => $this->faker->sentence(2)]);
    }
}
