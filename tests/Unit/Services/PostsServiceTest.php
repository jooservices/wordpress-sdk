<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Page;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class PostsServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testListReturnsTypedCollectionWithPaginationHeaders(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '10',
            'X-WP-TotalPages' => '2',
        ], json_encode([
            ['id' => 1, 'title' => ['rendered' => 'A']],
            ['id' => 2, 'title' => ['rendered' => 'B']],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/posts*', $sequence);

        $posts = $this->wordPress->posts()->list(new \JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery(perPage: 2));

        self::assertCount(2, $posts);
        self::assertSame(10, $posts->total);
        self::assertSame(2, $posts->totalPages);
        self::assertInstanceOf(Post::class, $posts->all()[0]);
        self::assertSame('B', $posts->all()[1]->title?->rendered);

        $request = $this->lastRequest();
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/wp-json/wp/v2/posts', $request->getUri()->getPath());
        $this->assertQuery($request, ['per_page' => 2]);
    }

    public function testGetSendsIdAndOptionalContext(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 4, 'title' => ['rendered' => 'Post']]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/4*', $sequence);

        $post = $this->wordPress->posts()->get(4, ['context' => 'edit']);

        self::assertSame(4, $post->id);
        $this->assertQuery($this->lastRequest(), ['context' => 'edit']);
    }

    public function testCreatePostsJsonPayload(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 9, 'status' => 'publish'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $post = $this->wordPress->posts()->create(['title' => 'New', 'status' => 'publish']);

        self::assertSame(9, $post->id);
        $request = $this->lastRequest();
        self::assertSame('POST', $request->getMethod());
        $this->assertJsonBody($request, ['title' => 'New', 'status' => 'publish']);
    }

    public function testUpdatePostsToItemPath(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 9, 'title' => ['rendered' => 'Updated']]));
        $this->httpFakes()->respond('POST', '*wp/v2/posts/9*', $sequence);

        $post = $this->wordPress->posts()->update(9, ['title' => 'Updated']);

        self::assertSame('Updated', $post->title?->rendered);
        $request = $this->lastRequest();
        self::assertSame('/wp-json/wp/v2/posts/9', $request->getUri()->getPath());
        $this->assertJsonBody($request, ['title' => 'Updated']);
    }

    public function testBuilderIsWiredToServiceAndMedia(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 10, 'title' => ['rendered' => 'Built']], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $post = $builder->title('Built')->slug('built')->create();

        self::assertSame(10, $post->id);
        $this->assertJsonBody($this->lastRequest(), [
            'title' => 'Built',
            'slug' => 'built',
            'status' => 'publish',
        ]);
    }

    public function testPagesServiceRoutesToPagesEndpoint(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [], json_encode([
            ['id' => 3, 'type' => 'page', 'title' => ['rendered' => 'About']],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/pages*', $sequence);

        $pages = $this->wordPress->pages()->list();

        self::assertInstanceOf(Page::class, $pages->all()[0]);
        self::assertSame('About', $pages->all()[0]->title?->rendered);
        self::assertSame('/wp-json/wp/v2/pages', $this->lastRequest()->getUri()->getPath());
    }

    public function testCommentsServiceRoutesToCommentsEndpoint(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1, 'author_name' => 'Jane']));
        $this->httpFakes()->respond('GET', '*wp/v2/comments/1*', $sequence);

        $comment = $this->wordPress->comments()->get(1);

        self::assertSame('Jane', $comment->author_name);
        self::assertSame('/wp-json/wp/v2/comments/1', $this->lastRequest()->getUri()->getPath());
    }
}
