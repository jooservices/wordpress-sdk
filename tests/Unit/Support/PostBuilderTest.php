<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Support;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContentBuilder;
use JOOservices\WordPress\Sdk\Support\PostBuilder;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;
use RuntimeException;

final class PostBuilderTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testBuildsPublishPayloadWithDefaults(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $post = $builder
            ->title('Hello')
            ->content('<p>Body</p>')
            ->excerpt('Teaser')
            ->categories([1, 2])
            ->tags([3])
            ->author(4)
            ->create();

        self::assertSame(1, $post->id);
        $this->assertJsonBody($this->lastRequest(), [
            'title' => 'Hello',
            'content' => '<p>Body</p>',
            'excerpt' => 'Teaser',
            'categories' => [1, 2],
            'tags' => [3],
            'author' => 4,
            'status' => 'publish',
        ]);
    }

    public function testContentBuilderIsRenderedIntoPayload(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $content = (new ContentBuilder())->text('Hello');
        $builder->content($content);

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->title('X')->create();

        $this->assertJsonBody($this->lastRequest(), [
            'title' => 'X',
            'content' => "<!-- wp:paragraph -->\n<p>Hello</p>\n<!-- /wp:paragraph -->",
            'status' => 'publish',
        ]);
    }

    public function testContentClosureReceivesBuilder(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $builder->content(function (ContentBuilder $builder): ContentBuilder {
            return $builder->heading('Section');
        });

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->title('X')->create();

        $this->assertJsonBody($this->lastRequest(), [
            'title' => 'X',
            'content' => "<!-- wp:heading -->\n<h2>Section</h2>\n<!-- /wp:heading -->",
            'status' => 'publish',
        ]);
    }

    public function testContentClosureMustReturnBuilder(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $this->expectException(RuntimeException::class);

        $builder->content(static fn(ContentBuilder $builder): string => 'not a builder');
    }

    public function testStatusCanBeOverridden(): void
    {
        $builder = $this->wordPress->posts()->builder()->title('Draft post')->status('draft');

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 2], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->create();

        $this->assertJsonBody($this->lastRequest(), ['title' => 'Draft post', 'status' => 'draft']);
    }

    public function testTitleIsRequired(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $this->expectException(RuntimeException::class);

        $builder->create();
    }

    public function testFeaturedImageId(): void
    {
        $builder = $this->wordPress->posts()->builder()->title('X')->featuredImageId(7);

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->create();

        $this->assertJsonBody($this->lastRequest(), [
            'title' => 'X',
            'featured_media' => 7,
            'status' => 'publish',
        ]);
    }

    public function testFeaturedImageUploadsViaMediaService(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sdk-featured');
        file_put_contents($file, 'img-data');

        try {
            $upload = new TestResponseSequence();
            $upload->push(TestResponse::json(['id' => 21, 'source_url' => 'https://example.test/u.png'], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/media*', $upload);

            $create = new TestResponseSequence();
            $create->push(TestResponse::json(['id' => 1], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/posts*', $create);

            $post = $this->wordPress->posts()->builder()
                ->title('With image')
                ->featuredImage($file, ['alt_text' => 'Alt'])
                ->create();

            self::assertSame(1, $post->id);
            $this->assertJsonBody($this->lastRequest(), [
                'title' => 'With image',
                'featured_media' => 21,
                'status' => 'publish',
            ]);
        } finally {
            unlink($file);
        }
    }

    public function testFeaturedImageRequiresMediaService(): void
    {
        $builder = new PostBuilder(
            $this->wordPress->posts(),
            mediaService: null,
        );

        $this->expectException(RuntimeException::class);

        $builder->featuredImage('/some/file.png');
    }

    public function testUpdateSendsPayloadAndExtras(): void
    {
        $builder = $this->wordPress->posts()->builder()->title('Renamed');

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 5, 'title' => ['rendered' => 'Renamed']]));
        $this->httpFakes()->respond('POST', '*wp/v2/posts/5*', $sequence);

        $post = $builder->update(5, ['status' => 'draft']);

        self::assertSame(5, $post->id);
        $this->assertJsonBody($this->lastRequest(), [
            'title' => 'Renamed',
            'status' => 'draft',
        ]);
    }

    public function testToArrayExposesPayload(): void
    {
        $builder = $this->wordPress->posts()->builder()->title('X')->slug('x');

        self::assertSame([
            'title' => 'X',
            'slug' => 'x',
            'status' => 'publish',
        ], $builder->toArray());
    }
}
