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

    public function testBuildsDraftPayloadByDefault(): void
    {
        $title = $this->faker->sentence(3);
        $content = sprintf('<p>%s</p>', $this->faker->sentence());
        $excerpt = $this->faker->sentence();
        $builder = $this->wordPress->posts()->builder();

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $post = $builder
            ->title($title)
            ->content($content)
            ->excerpt($excerpt)
            ->categories([1, 2])
            ->tags([3])
            ->author(4)
            ->create();

        self::assertSame(1, $post->id);
        $this->assertJsonBody($this->lastRequest(), [
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'categories' => [1, 2],
            'tags' => [3],
            'author' => 4,
            'status' => 'draft',
        ]);
    }

    public function testContentBuilderIsRenderedIntoPayload(): void
    {
        $text = $this->faker->sentence();
        $title = $this->faker->sentence(2);
        $builder = $this->wordPress->posts()->builder();

        $content = (new ContentBuilder())->text($text);
        $builder->content($content);

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->title($title)->create();

        $this->assertJsonBody($this->lastRequest(), [
            'title' => $title,
            'content' => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->",
            'status' => 'draft',
        ]);
    }

    public function testContentClosureReceivesBuilder(): void
    {
        $heading = $this->faker->sentence(2);
        $title = $this->faker->sentence(2);
        $builder = $this->wordPress->posts()->builder();

        $builder->content(function (ContentBuilder $builder) use ($heading): ContentBuilder {
            return $builder->heading($heading);
        });

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->title($title)->create();

        $this->assertJsonBody($this->lastRequest(), [
            'title' => $title,
            'content' => "<!-- wp:heading -->\n<h2>{$heading}</h2>\n<!-- /wp:heading -->",
            'status' => 'draft',
        ]);
    }

    public function testContentClosureMustReturnBuilder(): void
    {
        $invalidResult = $this->faker->word();
        $builder = $this->wordPress->posts()->builder();

        $this->expectException(RuntimeException::class);

        $builder->content(static fn(ContentBuilder $builder): string => $invalidResult); // @phpstan-ignore argument.type (runtime guard)
    }

    public function testStatusCanBeOverridden(): void
    {
        $title = $this->faker->sentence(2);
        $builder = $this->wordPress->posts()->builder()->title($title)->status('publish');

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 2], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->create();

        $this->assertJsonBody($this->lastRequest(), ['title' => $title, 'status' => 'publish']);
    }

    public function testTitleIsRequired(): void
    {
        $builder = $this->wordPress->posts()->builder();

        $this->expectException(RuntimeException::class);

        $builder->create();
    }

    public function testFeaturedImageId(): void
    {
        $title = $this->faker->sentence(2);
        $builder = $this->wordPress->posts()->builder()->title($title)->featuredImageId(7);

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/posts*', $sequence);

        $builder->create();

        $this->assertJsonBody($this->lastRequest(), [
            'title' => $title,
            'featured_media' => 7,
            'status' => 'draft',
        ]);
    }

    public function testFeaturedImageUploadsViaMediaService(): void
    {
        $title = $this->faker->sentence(2);
        $altText = $this->faker->words(2, true);
        $file = tempnam(sys_get_temp_dir(), 'sdk-featured');
        file_put_contents($file, $this->faker->text());

        try {
            $upload = new TestResponseSequence();
            $upload->push(TestResponse::json(['id' => 21, 'source_url' => $this->faker->imageUrl()], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/media*', $upload);

            $create = new TestResponseSequence();
            $create->push(TestResponse::json(['id' => 1], 201));
            $this->httpFakes()->respond('POST', '*wp/v2/posts*', $create);

            $post = $this->wordPress->posts()->builder()
                ->title($title)
                ->featuredImage($file, ['alt_text' => $altText])
                ->create();

            self::assertSame(1, $post->id);
            $this->assertJsonBody($this->lastRequest(), [
                'title' => $title,
                'featured_media' => 21,
                'status' => 'draft',
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

        $filePath = $this->faker->filePath();
        self::assertIsString($filePath);
        $builder->featuredImage($filePath);
    }

    public function testUpdateSendsPayloadAndExtras(): void
    {
        $title = $this->faker->sentence(2);
        $builder = $this->wordPress->posts()->builder()->title($title);

        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['id' => 5, 'title' => ['rendered' => $title]]));
        $this->httpFakes()->respond('POST', '*wp/v2/posts/5*', $sequence);

        $post = $builder->update(5, ['status' => 'draft']);

        self::assertSame(5, $post->id);
        $this->assertJsonBody($this->lastRequest(), [
            'title' => $title,
            'status' => 'draft',
        ]);
    }

    public function testToArrayExposesPayload(): void
    {
        $title = $this->faker->sentence(2);
        $slug = $this->faker->slug();
        $builder = $this->wordPress->posts()->builder()->title($title)->slug($slug);

        self::assertSame([
            'title' => $title,
            'slug' => $slug,
            'status' => 'draft',
        ], $builder->toArray());
    }
}
