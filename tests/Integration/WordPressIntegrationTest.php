<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Integration;

use Faker\Factory;
use Faker\Generator;
use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Button;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Heading;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Paragraph;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\Quote;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Blocks\Core\ReadMore;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\ContentBuilder;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\Contracts\BlockInterface;
use JOOservices\WordPress\Sdk\Support\ContentBuilder\GenericBlock;
use JOOservices\WordPress\Sdk\WordPressService;
use JOOservices\WordPress\Sdk\Tests\Support\CoreRouteSupport;
use PHPUnit\Framework\TestCase;

final class WordPressIntegrationTest extends TestCase
{
    private WordPressService $wordPress;

    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $url = getenv('WORDPRESS_URL');
        $username = getenv('WORDPRESS_USER');
        $password = getenv('WORDPRESS_APP_PASSWORD');

        if (! is_string($url) || ! is_string($username) || ! is_string($password)) {
            self::fail('WordPress integration environment is not configured.');
        }

        $this->wordPress = WordPressService::create(
            baseUrl: $url,
            username: $username,
            password: $password,
            retry: new RetryConfig(maxAttempts: 1),
            allowInsecureHttp: true,
        );
        $this->faker = Factory::create();
    }

    public function testAuthenticatedCoreWorkflows(): void
    {
        $index = $this->wordPress->discovery()->index();
        self::assertArrayHasKey('namespaces', $index);

        $routes = array_keys($this->wordPress->discovery()->routes());
        self::assertSame([], (new CoreRouteSupport())->unsupported($routes));

        $me = $this->wordPress->users()->me(['context' => 'edit']);
        self::assertSame('admin', $me->username);

        $title = $this->faker->sentence(4);
        $post = $this->wordPress->posts()->create([
            'title' => $title,
            'content' => $this->faker->paragraph(),
            'status' => 'draft',
        ]);

        try {
            self::assertGreaterThan(0, $post->id);
            self::assertSame($title, $post->title?->raw);

            $loaded = $this->wordPress->posts()->get($post->id, ['context' => 'edit']);
            self::assertSame($post->id, $loaded->id);

            $updatedTitle = $this->faker->sentence(5);
            $updated = $this->wordPress->posts()->update($post->id, ['title' => $updatedTitle]);
            self::assertSame($updatedTitle, $updated->title?->raw);

            $listed = $this->wordPress->posts()->list([
                'include' => [$post->id],
                'context' => 'edit',
                'status' => 'draft',
            ]);
            self::assertSame(1, $listed->total);
            self::assertSame($post->id, $listed->all()[0]->id);
        } finally {
            $deleted = $this->wordPress->posts()->delete($post->id, true);
            self::assertSame($post->id, $deleted->id);
        }

        $category = $this->wordPress->categories()->create(['name' => $this->faker->unique()->words(2, true)]);
        try {
            self::assertGreaterThan(0, $category->id);
        } finally {
            self::assertSame($category->id, $this->wordPress->categories()->delete($category->id, true)->id);
        }

        $passwords = $this->wordPress->applicationPasswords()->list('me');
        self::assertGreaterThanOrEqual(1, count($passwords));
        self::assertNotSame('', $this->wordPress->applicationPasswords()->introspect()->uuid);
    }

    public function testContentMediaTaxonomyAndPageWorkflows(): void
    {
        $category = $this->wordPress->categories()->create(['name' => $this->faker->unique()->words(2, true)]);
        $tag = $this->wordPress->tags()->create(['name' => $this->faker->unique()->word()]);
        $media = null;
        $post = null;
        $page = null;
        $comment = null;
        $batchPostIds = [];
        $temporary = tempnam(sys_get_temp_dir(), 'wordpress-sdk-');

        self::assertIsString($temporary);
        $image = $temporary . '.png';
        self::assertTrue(rename($temporary, $image));
        file_put_contents($image, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        ));

        try {
            $media = $this->wordPress->media()->upload($image, ['title' => 'SDK E2E image']);
            self::assertGreaterThan(0, $media->id);

            $post = $this->wordPress->posts()->create([
                'title' => $this->faker->sentence(4),
                'content' => $this->faker->paragraph(),
                'status' => 'publish',
                'featured_media' => $media->id,
                'categories' => [$category->id],
                'tags' => [$tag->id],
            ]);
            self::assertSame($media->id, $post->featured_media);

            $comment = $this->wordPress->comments()->create([
                'post' => $post->id,
                'content' => 'WordPress SDK E2E comment',
                'author_name' => 'SDK E2E',
                'author_email' => 'sdk@example.test',
            ]);
            self::assertSame($post->id, $comment->post);

            $page = $this->wordPress->pages()->create([
                'title' => $this->faker->sentence(3),
                'status' => 'draft',
            ]);
            self::assertGreaterThan(0, $page->id);
            self::assertNotEmpty($this->wordPress->autosaves()->resource('pages', $page->id)->create([
                'title' => 'Autosaved page title',
            ]));

            self::assertGreaterThanOrEqual(1, count($this->wordPress->postTypes()->list()));
            self::assertGreaterThanOrEqual(1, count($this->wordPress->statuses()->list()));
            self::assertGreaterThanOrEqual(1, count($this->wordPress->taxonomies()->list()));
            self::assertGreaterThanOrEqual(1, count($this->wordPress->themes()->list()));
            self::assertIsArray($this->wordPress->plugins()->list());
            self::assertIsArray($this->wordPress->blockTypes()->list());
            self::assertIsArray($this->wordPress->patterns()->patterns());
            self::assertIsArray($this->wordPress->patterns()->categories());
            self::assertIsArray($this->wordPress->fonts()->collections());
            self::assertIsArray($this->wordPress->icons()->collections());
            self::assertIsArray($this->wordPress->abilities()->list());
            self::assertIsArray($this->wordPress->settings()->get()->toArray());

            $batch = $this->wordPress->utility()->batch([
                ['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => 'Batch post one', 'status' => 'draft']],
                ['method' => 'POST', 'path' => '/wp/v2/posts', 'body' => ['title' => 'Batch post two', 'status' => 'draft']],
            ]);
            $responses = $batch['responses'] ?? null;
            self::assertIsArray($responses);
            self::assertCount(2, $responses);
            foreach ($responses as $response) {
                self::assertIsArray($response);
                $body = $response['body'] ?? null;
                self::assertIsArray($body);
                $id = $body['id'] ?? null;
                self::assertIsInt($id);
                $batchPostIds[] = $id;
            }
        } finally {
            foreach ($batchPostIds as $batchPostId) {
                $this->wordPress->posts()->delete($batchPostId, true);
            }
            if ($comment !== null) {
                $this->wordPress->comments()->delete($comment->id, true);
            }
            if ($page !== null) {
                $this->wordPress->pages()->delete($page->id, true);
            }
            if ($post !== null) {
                $this->wordPress->posts()->delete($post->id, true);
            }
            if ($media !== null) {
                $this->wordPress->media()->delete($media->id, true);
            }
            $this->wordPress->tags()->delete($tag->id, true);
            $this->wordPress->categories()->delete($category->id, true);
            unlink($image);
        }
    }

    public function testContentBuilderPostRoundTrip(): void
    {
        $sample = $this->contentBuilderReviewSample();
        $retainRequested = getenv('WORDPRESS_RETAIN_REVIEW_SAMPLE') === '1';
        $post = null;
        $retainAfterSuccess = false;

        try {
            $post = $this->wordPress->posts()->builder()
                ->title($sample['title'])
                ->slug($sample['marker'])
                ->content($sample['builder'])
                ->status($retainRequested ? 'publish' : 'draft')
                ->create();

            self::assertGreaterThan(0, $post->id);
            self::assertSame($sample['marker'], $post->slug);
            $loaded = $this->wordPress->posts()->get($post->id, ['context' => 'edit']);
            self::assertSame($post->id, $loaded->id);
            $rawContent = $loaded->content?->raw;
            self::assertIsString($rawContent);
            $this->assertContentBuilderReviewSample($rawContent, $sample);

            if ($retainRequested) {
                fwrite(STDERR, sprintf("Retained WordPress review sample: %s\n", $this->reviewBrowserUrl($post->link)));
                $retainAfterSuccess = true;
            }
        } finally {
            if ($post !== null && ! $retainAfterSuccess) {
                self::assertSame($post->id, $this->wordPress->posts()->delete($post->id, true)->id);
            }
        }
    }

    /**
     * @return array{
     *     marker: string,
     *     title: string,
     *     builder: ContentBuilder,
     *     headings: list<string>,
     *     escaped_quote: string,
     *     escaped_citation: string,
     *     raw_quote: string,
     *     raw_citation: string,
     *     button_url: string,
     *     read_more_label: string,
     *     serialization_probe: string,
     *     generic_attributes: array<string, mixed>,
     *     generic_content: string
     * }
     */
    private function contentBuilderReviewSample(): array
    {
        $marker = 'sdk-content-builder-review-' . str_replace('-', '', strtolower($this->faker->uuid()));
        $headings = [
            'Quote: legacy raw HTML by default',
            'Quote: opt-in HTML escaping',
            'Button: safe HTTPS link',
            'Read More marker',
            'Custom block: safe JSON attributes',
        ];
        $rawQuote = sprintf('<em>%s</em> & %s', $this->faker->word(), $this->faker->word());
        $rawCitation = '<strong>' . $this->faker->name() . '</strong>';
        $escapedQuote = sprintf('Safe inline <strong>%s</strong> and <em>%s</em>.', $this->faker->word(), $this->faker->word());
        $escapedCitation = '<em>' . $this->faker->name() . '</em>';
        $buttonUrl = sprintf('https://%s/%s', $this->faker->domainName(), $this->faker->slug());
        $readMoreLabel = $this->faker->word();
        $serializationProbe = sprintf('--> <script>%s</script>', $this->faker->word());
        $genericAttributes = [
            'sampleMarker' => $marker,
            'label' => $this->faker->word(),
            'serializationProbe' => $serializationProbe,
        ];
        $genericContent = $this->faker->sentence();
        $builder = (new ContentBuilder())
            ->heading($headings[0])
            ->quote($rawQuote, $rawCitation)
            ->heading($headings[1])
            ->quote($escapedQuote, $escapedCitation, escapeHtml: true)
            ->heading($headings[2])
            ->button($this->faker->sentence(2), $buttonUrl)
            ->heading($headings[3])
            ->text('The Read More label stored by this block is: ' . $readMoreLabel)
            ->readMore($readMoreLabel)
            ->heading($headings[4])
            ->text('Visible serialization probe: ' . $serializationProbe)
            ->block('joo-sdk/review-sample', $genericAttributes, $genericContent);

        return [
            'marker' => $marker,
            'title' => 'ContentBuilder E2E Review Sample ' . $marker,
            'builder' => $builder,
            'headings' => $headings,
            'escaped_quote' => $escapedQuote,
            'escaped_citation' => $escapedCitation,
            'raw_quote' => $rawQuote,
            'raw_citation' => $rawCitation,
            'button_url' => $buttonUrl,
            'read_more_label' => $readMoreLabel,
            'serialization_probe' => $serializationProbe,
            'generic_attributes' => $genericAttributes,
            'generic_content' => $genericContent,
        ];
    }

    /**
     * @param array{
     *     headings: list<string>,
     *     escaped_quote: string,
     *     escaped_citation: string,
     *     raw_quote: string,
     *     raw_citation: string,
     *     button_url: string,
     *     read_more_label: string,
     *     serialization_probe: string,
     *     generic_attributes: array<string, mixed>,
     *     generic_content: string
     * } $sample
     */
    private function assertContentBuilderReviewSample(string $rawContent, array $sample): void
    {
        foreach ($sample['headings'] as $heading) {
            self::assertStringContainsString($heading, $rawContent);
        }

        self::assertStringContainsString($sample['read_more_label'], $rawContent);
        self::assertStringContainsString(
            htmlspecialchars($sample['serialization_probe'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $rawContent,
        );
        self::assertStringContainsString(
            sprintf(
                '<p>%s</p><cite>%s</cite>',
                $sample['raw_quote'],
                $sample['raw_citation'],
            ),
            $rawContent,
        );
        self::assertStringContainsString(
            sprintf(
                '<p>%s</p><cite>%s</cite>',
                htmlspecialchars($sample['escaped_quote'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($sample['escaped_citation'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ),
            $rawContent,
        );
        self::assertStringContainsString('href="' . $sample['button_url'] . '"', $rawContent);
        self::assertStringContainsString('<!--more ' . $sample['read_more_label'] . '-->', $rawContent);
        self::assertStringContainsString('\\u002d\\u002d\\u003e \\u003cscript\\u003e', strtolower($rawContent));
        self::assertStringContainsString('\\u003c/script\\u003e', strtolower($rawContent));

        $parsed = ContentBuilder::parse($rawContent)->getBlocks();
        self::assertSame(
            [
                Heading::class,
                Quote::class,
                Heading::class,
                Quote::class,
                Heading::class,
                Button::class,
                Heading::class,
                Paragraph::class,
                ReadMore::class,
                Heading::class,
                Paragraph::class,
                GenericBlock::class,
            ],
            array_map(static fn(BlockInterface $block): string => $block::class, $parsed),
        );

        self::assertInstanceOf(Quote::class, $parsed[1]);
        self::assertSame($sample['raw_quote'], $parsed[1]->content);
        self::assertInstanceOf(Quote::class, $parsed[3]);
        self::assertSame(
            htmlspecialchars($sample['escaped_quote'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $parsed[3]->content,
        );
        self::assertInstanceOf(Button::class, $parsed[5]);
        self::assertSame($sample['button_url'], $parsed[5]->url);
        self::assertInstanceOf(ReadMore::class, $parsed[8]);
        self::assertSame($sample['read_more_label'], $parsed[8]->customText);
        self::assertInstanceOf(GenericBlock::class, $parsed[11]);
        self::assertSame($sample['generic_attributes'], $parsed[11]->attributes);
        self::assertSame($sample['generic_content'], trim($parsed[11]->content));
    }

    public function testUserRevisionResourcePasswordUpdate(): void
    {
        $me = $this->wordPress->users()->me();
        $originalDescription = $me->description;

        try {
            $description = $this->faker->sentence();
            $updated = $this->wordPress->users()->updateMe(['description' => $description]);
            self::assertSame($description, $updated->description);

            $title = $this->faker->sentence(4);
            $post = $this->wordPress->posts()->create([
                'title' => $title,
                'content' => $this->faker->paragraph(),
                'status' => 'draft',
            ]);

            try {
                $this->wordPress->posts()->update($post->id, ['title' => $this->faker->sentence(5)]);
                $revisions = $this->wordPress->posts()->revisions($post->id)->list();
                self::assertNotEmpty($revisions);

                $loaded = $this->wordPress->resource('posts')->get($post->id, ['context' => 'edit']);
                self::assertSame($post->id, $loaded->id);

                $types = $this->wordPress->custom()->get('wp/v2/types');
                self::assertArrayHasKey('post', $types);

                $category = $this->wordPress->terms('categories')->create([
                    'name' => $this->faker->unique()->words(2, true),
                ]);
                try {
                    self::assertGreaterThan(0, $category->id);
                } finally {
                    $this->wordPress->terms('categories')->delete($category->id, true);
                }

                $families = $this->wordPress->fonts()->families();
                self::assertIsArray($families);

                $created = $this->wordPress->applicationPasswords()->create('me', [
                    'name' => 'sdk-e2e-' . $this->faker->unique()->lexify('????'),
                ]);
                try {
                    self::assertNotSame('', $created->uuid);

                    $renamed = $this->wordPress->applicationPasswords()->update('me', $created->uuid, [
                        'name' => 'sdk-e2e-renamed',
                    ]);
                    self::assertSame('sdk-e2e-renamed', $renamed->name);
                } finally {
                    $this->wordPress->applicationPasswords()->delete('me', $created->uuid);
                }
            } finally {
                $this->wordPress->posts()->delete($post->id, true);
            }
        } finally {
            $this->wordPress->users()->updateMe(['description' => $originalDescription]);
        }
    }

    private function reviewBrowserUrl(string $postLink): string
    {
        $reviewBaseUrl = getenv('WORDPRESS_REVIEW_BASE_URL');
        if (! is_string($reviewBaseUrl) || trim($reviewBaseUrl) === '') {
            return $postLink;
        }

        $postPath = parse_url($postLink, PHP_URL_PATH);
        if (! is_string($postPath) || $postPath === '') {
            return rtrim($reviewBaseUrl, '/');
        }

        $reviewUrl = rtrim($reviewBaseUrl, '/') . '/' . ltrim($postPath, '/');
        $query = parse_url($postLink, PHP_URL_QUERY);

        return is_string($query) && $query !== '' ? $reviewUrl . '?' . $query : $reviewUrl;
    }
}
