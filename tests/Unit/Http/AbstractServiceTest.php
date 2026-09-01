<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Http;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Exceptions\NotFoundException;
use JOOservices\WordPress\Sdk\Services\PostsService;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class AbstractServiceTest extends TestCase
{
    private WordPressService $wordPress;

    private PostsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
        $this->service = $this->wordPress->posts();
    }

    public function testQueryOptionsAreAppendedToUri(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'title' => ['rendered' => 'A']]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/1*', $sequence);

        $post = $this->service->get(1, ['context' => 'edit', 'password' => 'x']);

        self::assertSame(1, $post->id);
        $this->assertQuery($this->lastRequest(), ['context' => 'edit', 'password' => 'x']);
        self::assertSame('/wp-json/wp/v2/posts/1', $this->lastRequest()->getUri()->getPath());
    }

    public function testJsonBodyOptionsAreSent(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 9, 'title' => ['rendered' => 'New']], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $post = $this->service->create(['title' => 'New', 'status' => 'publish']);

        self::assertSame(9, $post->id);
        $request = $this->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertJsonBody($request, ['title' => 'New', 'status' => 'publish']);
    }

    public function testErrorStatusThrowsMappedException(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(404, [], json_encode([
            'code' => 'rest_post_invalid_id',
            'message' => 'Invalid post ID.',
            'data' => ['status' => 404],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/999*', $sequence);

        $this->expectException(NotFoundException::class);

        $this->service->get(999);
    }

    public function testDeleteUnwrapsForceDeletePayload(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            'deleted' => true,
            'previous' => ['id' => 5, 'title' => ['rendered' => 'Gone']],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('DELETE', '*wp/v2/posts/5*', $sequence);

        $post = $this->service->delete(5, force: true);

        self::assertSame(5, $post->id);
        self::assertSame('Gone', $post->title?->rendered);
        $this->assertQuery($this->lastRequest(), ['force' => 'true']);
    }

    public function testDeleteDecodesTrashedPostResponse(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 5, 'status' => 'trash']));
        $this->httpFakes()->respond('DELETE', '*wp/v2/posts/5*', $sequence);

        $post = $this->service->delete(5);

        self::assertSame(5, $post->id);
        self::assertSame('trash', $post->status);
    }

    public function testDeleteWithEmptyBodyReturnsDefaults(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], ''));
        $this->httpFakes()->respond('DELETE', '*wp/v2/posts/5*', $sequence);

        $post = $this->service->delete(5);

        self::assertSame(0, $post->id);
    }

    public function testCursorStreamsAcrossPages(): void
    {
        $this->respondPages(3, 2);

        $posts = iterator_to_array($this->service->cursor(['per_page' => 2]), false);

        self::assertCount(3, $posts);
        self::assertSame([1, 2, 3], array_map(static fn(Post $post): int => $post->id, $posts));
        self::assertCount(2, $this->httpFakes()->recorded());
        $this->assertQuery($this->lastRequest(), ['per_page' => 2, 'page' => 2]);
    }

    public function testCursorHonorsExplicitStartPage(): void
    {
        $this->respondPages(5, 2);

        $posts = iterator_to_array($this->service->cursor(['per_page' => 2, 'page' => 2]), false);

        self::assertSame([3, 4, 5], array_map(static fn(Post $post): int => $post->id, $posts));
    }

    public function testCursorStopsWhenTotalPagesExhausted(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '0',
            'X-WP-TotalPages' => '0',
        ], '[]'));
        $this->httpFakes()->respond('GET', '*wp/v2/posts?page=1*', $sequence);

        $posts = iterator_to_array($this->service->cursor(), false);

        self::assertSame([], $posts);
    }

    public function testAllCollectsEveryPage(): void
    {
        $this->respondPages(3, 2);

        $posts = $this->service->all(['per_page' => 2]);

        self::assertCount(3, $posts);
        self::assertSame([1, 2, 3], array_map(static fn(Post $post): int => $post->id, $posts));
    }

    public function testEachStopsEarlyWhenCallbackReturnsFalse(): void
    {
        $this->respondPages(3, 2);

        $seen = [];
        $this->service->each(function (Post $post) use (&$seen): bool {
            $seen[] = $post->id;

            return $post->id !== 2;
        }, ['per_page' => 2]);

        self::assertSame([1, 2], $seen);
        self::assertCount(1, $this->httpFakes()->recorded());
    }

    public function testEachVisitsEveryItem(): void
    {
        $this->respondPages(3, 2);

        $seen = [];
        $this->service->each(static function (Post $post) use (&$seen) {
            $seen[] = $post->id;
        }, ['per_page' => 2]);

        self::assertSame([1, 2, 3], $seen);
    }

    private function respondPages(int $total, int $perPage): void
    {
        $totalPages = $perPage === 0 ? 0 : (int) ceil($total / $perPage);
        for ($page = 1; $page <= $totalPages; $page++) {
            $items = [];
            for ($i = ($page - 1) * $perPage + 1; $i <= min($total, $page * $perPage); $i++) {
                $items[] = ['id' => $i, 'title' => ['rendered' => 'Post ' . $i]];
            }

            $sequence = new TestResponseSequence();
            $sequence->push(TestResponse::make(200, [
                'X-WP-Total' => (string) $total,
                'X-WP-TotalPages' => (string) $totalPages,
            ], json_encode($items, JSON_THROW_ON_ERROR)));
            $this->httpFakes()->respond('GET', sprintf('*wp/v2/posts?page=%d*', $page), $sequence);
        }
    }
}
