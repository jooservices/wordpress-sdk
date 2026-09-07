<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Post;
use JOOservices\WordPress\Sdk\Data\Query\ListPostsQuery;
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

        $posts = $this->wordPress->posts()->list(new ListPostsQuery(perPage: 2));

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

    public function testListPreservesEmbeddedDataWhenRequested(): void
    {
        $authorName = $this->faker->name();
        $embedded = ['author' => [['id' => $this->faker->numberBetween(1), 'name' => $authorName]]];
        $links = ['author' => [['href' => $this->faker->url(), 'embeddable' => true]]];
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json([[
            'id' => $this->faker->numberBetween(1),
            '_embedded' => $embedded,
            '_links' => $links,
        ]]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts*', $sequence);

        $post = $this->wordPress->posts()->list(new ListPostsQuery(embed: true))->all()[0];

        self::assertSame($embedded, $post->_embedded);
        self::assertSame($links, $post->_links);
        $this->assertQuery($this->lastRequest(), ['_embed' => 'true']);
    }

    public function testCreatePostsJsonPayload(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 9, 'status' => 'publish'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $title = $this->faker->sentence(3);
        $post = $this->wordPress->posts()->create(['title' => $title, 'status' => 'publish']);

        self::assertSame(9, $post->id);
        $request = $this->lastRequest();
        self::assertSame('POST', $request->getMethod());
        $this->assertJsonBody($request, ['title' => $title, 'status' => 'publish']);
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
            'status' => 'draft',
        ]);
    }

    public function testRevisionsAndAutosavesNestUnderPosts(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::json([['id' => 2]]));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/9/revisions*', $list);

        $revisions = $this->wordPress->posts()->revisions(9)->list();

        self::assertSame([['id' => 2]], $revisions);
        self::assertSame('/wp-json/wp/v2/posts/9/revisions', $this->lastRequest()->getUri()->getPath());

        $autosaves = new TestResponseSequence();
        $autosaves->push(TestResponse::json(['id' => 3], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts/9/autosaves*', $autosaves);

        $created = $this->wordPress->posts()->autosaves(9)->create(['title' => $this->faker->sentence(2)]);

        self::assertSame(['id' => 3], $created);
        self::assertSame('/wp-json/wp/v2/posts/9/autosaves', $this->lastRequest()->getUri()->getPath());
    }
}
