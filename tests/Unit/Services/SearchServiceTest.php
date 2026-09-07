<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\ApplicationPassword;
use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class SearchServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testSearchReturnsTypedResults(): void
    {
        $title = $this->faker->sentence(2);
        $url = $this->faker->url();
        $search = $this->faker->word();
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 2, 'title' => $title, 'url' => $url, 'type' => 'post', 'subtype' => 'post'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/search*', $sequence);

        $results = $this->wordPress->search()->search(['search' => $search]);

        self::assertInstanceOf(SearchResult::class, $results->all()[0]);
        self::assertSame($title, $results->all()[0]->title);
        $this->assertQuery($this->lastRequest(), ['search' => $search]);
    }

    public function testSettingsGetAndUpdate(): void
    {
        $title = $this->faker->sentence(2);
        $updatedTitle = $this->faker->sentence(2);
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['title' => $title, 'users_can_register' => 0]));
        $this->httpFakes()->respond('GET', '*wp/v2/settings*', $sequence);

        $settings = $this->wordPress->settings()->get();

        self::assertSame($title, $settings->get('title'));

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['title' => $updatedTitle]));
        $this->httpFakes()->respond('POST', '*wp/v2/settings*', $update);

        $updated = $this->wordPress->settings()->update(['title' => $updatedTitle]);

        self::assertSame($updatedTitle, $updated->get('title'));
        $this->assertJsonBody($this->lastRequest(), ['title' => $updatedTitle]);
    }

    public function testApplicationPasswordsCrud(): void
    {
        $uuid = $this->faker->uuid();
        $createdUuid = $this->faker->uuid();
        $name = $this->faker->word();
        $generatedPassword = $this->faker->password();
        $list = new TestResponseSequence();
        $list->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['uuid' => $uuid, 'name' => $name],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/users/me/application-passwords*', $list);

        $passwords = $this->wordPress->applicationPasswords()->list('me');

        self::assertInstanceOf(ApplicationPassword::class, $passwords->all()[0]);
        self::assertSame('/wp-json/wp/v2/users/me/application-passwords', $this->lastRequest()->getUri()->getPath());

        $create = new TestResponseSequence();
        $create->push(TestResponse::json([
            'uuid' => $createdUuid,
            'name' => $name,
            'password' => $generatedPassword,
        ], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/users/me/application-passwords*', $create);

        $created = $this->wordPress->applicationPasswords()->create('me', ['name' => $name]);

        self::assertSame($generatedPassword, $created->password);

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/users/me/application-passwords/' . $createdUuid . '*', $delete);

        $result = $this->wordPress->applicationPasswords()->delete('me', $createdUuid);

        self::assertSame(['deleted' => true], $result);
    }

    public function testApplicationPasswordsDeleteAll(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/users/5/application-passwords*', $sequence);

        $result = $this->wordPress->applicationPasswords()->deleteAll(5);

        self::assertSame(['deleted' => true], $result);
        self::assertSame('/wp-json/wp/v2/users/5/application-passwords', $this->lastRequest()->getUri()->getPath());
    }

    public function testDiscoveryIndexRoutesAndSchema(): void
    {
        $name = $this->faker->word();
        $index = new TestResponseSequence();
        $index->push(TestResponse::json([
            'name' => $name,
            'namespaces' => ['wp/v2'],
            'routes' => ['/wp/v2' => ['namespace' => 'wp/v2']],
        ]));
        $index->push(TestResponse::json([
            'name' => $name,
            'namespaces' => ['wp/v2'],
            'routes' => ['/wp/v2' => ['namespace' => 'wp/v2']],
        ]));
        $this->httpFakes()->respond('GET', '*', $index);

        self::assertSame($name, $this->wordPress->discovery()->index()['name']);
        self::assertSame(['/wp/v2' => ['namespace' => 'wp/v2']], $this->wordPress->discovery()->routes());
        self::assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function testDiscoveryRoutesWithoutRoutesKey(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['name' => $this->faker->word()]));
        $this->httpFakes()->respond('GET', '*', $sequence);

        self::assertSame([], $this->wordPress->discovery()->routes());
    }

    public function testDiscoverySchemaUsesOptionsVerb(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['schema' => ['type' => 'object']]));
        $this->httpFakes()->respond('OPTIONS', '*wp/v2/posts*', $sequence);

        $schema = $this->wordPress->discovery()->schema('wp/v2/posts');

        self::assertSame(['schema' => ['type' => 'object']], $schema);
        $request = $this->lastRequest();
        self::assertSame('OPTIONS', $request->getMethod());
        self::assertSame('/wp-json/wp/v2/posts', $request->getUri()->getPath());
    }

    public function testCustomEndpointVerbsAndPathNormalization(): void
    {
        $name = $this->faker->word();
        $updatedName = $this->faker->word();
        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['items' => []]));
        $this->httpFakes()->respond('GET', '*my-plugin/v1/items*', $get);

        $items = $this->wordPress->custom()->get('my-plugin//v1/items/', ['page' => 1]);

        self::assertSame(['items' => []], $items);
        self::assertSame('/wp-json/my-plugin/v1/items', $this->lastRequest()->getUri()->getPath());
        $this->assertQuery($this->lastRequest(), ['page' => 1]);

        $post = new TestResponseSequence();
        $post->push(TestResponse::json(['id' => 1], 201));
        $this->httpFakes()->respond('POST', '*my-plugin/v1/items*', $post);

        $created = $this->wordPress->custom()->post('my-plugin/v1/items', ['name' => $name]);

        self::assertSame(['id' => 1], $created);
        $this->assertJsonBody($this->lastRequest(), ['name' => $name]);

        $patch = new TestResponseSequence();
        $patch->push(TestResponse::json(['id' => 1, 'name' => $updatedName]));
        $this->httpFakes()->respond('PATCH', '*my-plugin/v1/items*', $patch);

        $updated = $this->wordPress->custom()->patch('my-plugin/v1/items/1', ['name' => $updatedName]);

        self::assertSame(['id' => 1, 'name' => $updatedName], $updated);
        self::assertSame('PATCH', $this->lastRequest()->getMethod());

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*my-plugin/v1/items/1*', $delete);

        self::assertSame(['deleted' => true], $this->wordPress->custom()->delete('my-plugin/v1/items/1'));
    }

    public function testCustomEndpointRejectsAbsoluteUrls(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->wordPress->custom()->get('https://evil.example.com/api');
    }

    public function testRevisionsScopedResources(): void
    {
        $title = $this->faker->sentence(2);
        $list = new TestResponseSequence();
        $list->push(TestResponse::make(200, [], json_encode([
            ['id' => 1, 'title' => ['rendered' => $title]],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/9/revisions*', $list);

        $revisions = $this->wordPress->revisions()->posts(9)->list();

        self::assertSame([['id' => 1, 'title' => ['rendered' => $title]]], $revisions);
        self::assertSame('/wp-json/wp/v2/posts/9/revisions', $this->lastRequest()->getUri()->getPath());

        $get = new TestResponseSequence();
        $get->push(TestResponse::json(['id' => 2]));
        $this->httpFakes()->respond('GET', '*wp/v2/pages/3/revisions/2*', $get);

        self::assertSame(['id' => 2], $this->wordPress->revisions()->pages(3)->get(2));

        $blockRevisions = new TestResponseSequence();
        $blockRevisions->push(TestResponse::json(['id' => 4]));
        $this->httpFakes()->respond('GET', '*wp/v2/blocks/5/revisions*', $blockRevisions);

        self::assertSame(['id' => 4], $this->wordPress->revisions()->blocks(5)->list());

        $templateRevisions = new TestResponseSequence();
        $templateRevisions->push(TestResponse::json(['id' => 5]));
        $this->httpFakes()->respond('GET', '*wp/v2/templates/theme%2Findex/revisions*', $templateRevisions);
        self::assertSame(
            ['id' => 5],
            $this->wordPress->revisions()->resource('templates', 'theme/index')->list(),
        );

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/posts/9/revisions/2*', $delete);

        self::assertSame(['deleted' => true], $this->wordPress->revisions()->posts(9)->delete(2));
    }
}
