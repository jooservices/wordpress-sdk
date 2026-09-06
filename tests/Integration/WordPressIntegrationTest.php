<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Integration;

use Faker\Factory;
use Faker\Generator;
use JOOservices\Client\Resilience\RetryConfig;
use JOOservices\WordPress\Sdk\WordPressService;
use JOOservices\WordPress\Sdk\Support\CoreRouteSupport;
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

    public function testCoreAuthenticatedWorkflowsAgainstWordPress(): void
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
    public function testUsersRevisionsCustomResourceAndApplicationPasswordUpdate(): void
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
                self::assertNotSame('', $created->uuid);

                $renamed = $this->wordPress->applicationPasswords()->update('me', $created->uuid, [
                    'name' => 'sdk-e2e-renamed',
                ]);
                self::assertSame('sdk-e2e-renamed', $renamed->name);
                $this->wordPress->applicationPasswords()->delete('me', $created->uuid);
            } finally {
                $this->wordPress->posts()->delete($post->id, true);
            }
        } finally {
            $this->wordPress->users()->updateMe(['description' => $originalDescription]);
        }
    }
}
