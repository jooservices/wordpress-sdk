<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Http;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Data\Status;
use JOOservices\WordPress\Sdk\Exceptions\ServerException;
use JOOservices\WordPress\Sdk\Http\ResponseDecoder;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

final class ResponseDecoderTest extends TestCase
{
    private ResponseDecoder $decoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decoder = new ResponseDecoder();
    }

    public function testDecodeItemHydratesDtoFromJsonBody(): void
    {
        $response = TestResponse::make(200, [], json_encode([
            'id' => '42',
            'title' => ['rendered' => 'Hello', 'raw' => 'Hello'],
            'template' => false,
            'categories' => ['1', 2],
            '_links' => ['self' => [['href' => '/x']]],
        ], JSON_THROW_ON_ERROR));

        $post = $this->decoder->decodeItem($response, Post::class);

        self::assertInstanceOf(Post::class, $post);
        self::assertSame(42, $post->id);
        self::assertSame('Hello', $post->title?->rendered);
        self::assertSame('', $post->template);
        self::assertSame([1, 2], $post->categories);
    }

    public function testDecodeItemRejectsHtmlBody(): void
    {
        $response = TestResponse::make(200, [], '<!DOCTYPE html><html><body>error</body></html>');

        $this->expectException(ServerException::class);

        $this->decoder->decodeItem($response, Post::class);
    }

    public function testDecodeItemRejectsInvalidJson(): void
    {
        $response = TestResponse::make(200, [], 'not json');

        $this->expectException(ServerException::class);

        $this->decoder->decodeItem($response, Post::class);
    }

    public function testDecodeFailureLogsOnlyNonSensitiveMetadata(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
            public array $records = [];

            /**
             * @param array<string, mixed> $context
             */
            public function log($level, Stringable|string $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };
        $decoder = new ResponseDecoder($logger);
        $body = '{"password":"abcd efgh ijkl mnop"';

        try {
            $decoder->decodeItem(TestResponse::make(200, [], $body), Post::class);
            self::fail('Expected invalid JSON to be rejected.');
        } catch (ServerException) {
            self::assertCount(1, $logger->records);
        }

        $context = $logger->records[0]['context'];
        self::assertArrayNotHasKey('body', $context);
        self::assertSame(strlen($body), $context['body_length'] ?? null);
        self::assertSame(hash('sha256', $body), $context['body_sha256'] ?? null);
        self::assertStringNotContainsString('abcd', json_encode($context, JSON_THROW_ON_ERROR));
    }

    public function testDecodeListUsesPaginationHeaders(): void
    {
        $response = TestResponse::make(200, [
            'X-WP-Total' => '2',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 1, 'title' => ['rendered' => 'A']],
            ['id' => 2, 'title' => ['rendered' => 'B']],
        ], JSON_THROW_ON_ERROR));

        $collection = $this->decoder->decodeList($response, Post::class);

        self::assertCount(2, $collection);
        self::assertSame(2, $collection->total);
        self::assertSame(1, $collection->totalPages);
        self::assertInstanceOf(Post::class, $collection->all()[0]);
        self::assertSame('A', $collection->all()[0]->title?->rendered);
    }

    public function testDecodeListWithoutHeadersFallsBackToItemCount(): void
    {
        $response = TestResponse::make(200, [], json_encode([
            ['id' => 1, 'title' => ['rendered' => 'A']],
        ], JSON_THROW_ON_ERROR));

        $collection = $this->decoder->decodeList($response, Post::class);

        self::assertSame(1, $collection->total);
        self::assertSame(1, $collection->totalPages);
    }

    public function testDecodeListHandlesAssocPayload(): void
    {
        $response = TestResponse::make(200, [], json_encode([
            'publish' => ['name' => 'Publish', 'public' => true],
            'draft' => ['name' => 'Draft', 'public' => false],
        ], JSON_THROW_ON_ERROR));

        $collection = $this->decoder->decodeList($response, Status::class);

        self::assertCount(2, $collection);
        self::assertSame('Publish', $collection->all()[0]->name);
        self::assertTrue($collection->all()[0]->public);
    }

    public function testDecodeListSkipsNonArrayMapValues(): void
    {
        $response = TestResponse::make(200, [], json_encode([
            'publish' => ['name' => 'Publish'],
            'broken' => 'not-an-object',
        ], JSON_THROW_ON_ERROR));

        $collection = $this->decoder->decodeList($response, Status::class);

        self::assertCount(1, $collection);
    }

    public function testDecodeArrayReturnsList(): void
    {
        $response = TestResponse::make(200, [], json_encode([
            ['id' => 1, 'title' => ['rendered' => 'A']],
            ['id' => 2, 'title' => ['rendered' => 'B']],
        ], JSON_THROW_ON_ERROR));

        $items = $this->decoder->decodeArray($response, Post::class);

        self::assertCount(2, $items);
        self::assertSame(1, $items[0]->id);
    }

    public function testDecodeArrayRejectsAssocPayload(): void
    {
        $response = TestResponse::make(200, [], json_encode([
            'publish' => ['name' => 'Publish'],
        ], JSON_THROW_ON_ERROR));

        $this->expectException(ServerException::class);

        $this->decoder->decodeArray($response, Status::class);
    }

    public function testDeserializeFromArray(): void
    {
        $post = $this->decoder->deserialize(['id' => 7, 'slug' => 'hello'], Post::class);

        self::assertInstanceOf(Post::class, $post);
        self::assertSame(7, $post->id);
        self::assertSame('hello', $post->slug);
    }

    public function testDeserializeEmptyArrayUsesDefaults(): void
    {
        $post = $this->decoder->deserialize([], Post::class);

        self::assertSame(0, $post->id);
        self::assertSame('', $post->slug);
    }

    public function testDeserializeFailureWrapsAsServerException(): void
    {
        $this->expectException(ServerException::class);

        $this->decoder->deserialize(['id' => 'not-an-int'], Post::class);
    }
}
