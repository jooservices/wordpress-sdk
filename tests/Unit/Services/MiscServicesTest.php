<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\ApplicationPassword;
use JOOservices\WordPress\Sdk\Data\SearchResult;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class MiscServicesTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testSearchReturnsTypedResults(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 2, 'title' => 'Found', 'url' => 'https://example.com/x/', 'type' => 'post', 'subtype' => 'post'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/search*', $sequence);

        $results = $this->wordPress->search()->search(['search' => 'found']);

        self::assertInstanceOf(SearchResult::class, $results->all()[0]);
        self::assertSame('Found', $results->all()[0]->title);
        $this->assertQuery($this->lastRequest(), ['search' => 'found']);
    }

    public function testSettingsGetAndUpdate(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['title' => 'My Site', 'users_can_register' => 0]));
        $this->httpFakes()->respond('GET', '*wp/v2/settings*', $sequence);

        $settings = $this->wordPress->settings()->get();

        self::assertSame('My Site', $settings->get('title'));

        $update = new TestResponseSequence();
        $update->push(TestResponse::json(['title' => 'Renamed']));
        $this->httpFakes()->respond('POST', '*wp/v2/settings*', $update);

        $updated = $this->wordPress->settings()->update(['title' => 'Renamed']);

        self::assertSame('Renamed', $updated->get('title'));
        $this->assertJsonBody($this->lastRequest(), ['title' => 'Renamed']);
    }

    public function testApplicationPasswordsCrud(): void
    {
        $list = new TestResponseSequence();
        $list->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['uuid' => 'abc', 'name' => 'Worker'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/users/me/application-passwords*', $list);

        $passwords = $this->wordPress->applicationPasswords()->list('me');

        self::assertInstanceOf(ApplicationPassword::class, $passwords->all()[0]);
        self::assertSame('/wp-json/wp/v2/users/me/application-passwords', $this->lastRequest()->getUri()->getPath());

        $create = new TestResponseSequence();
        $create->push(TestResponse::json(['uuid' => 'new', 'name' => 'Worker', 'password' => 'abcd efgh ijkl'], 201));
        $this->httpFakes()->respond('POST', '*wp/v2/users/me/application-passwords*', $create);

        $created = $this->wordPress->applicationPasswords()->create('me', ['name' => 'Worker']);

        self::assertSame('abcd efgh ijkl', $created->password);

        $delete = new TestResponseSequence();
        $delete->push(TestResponse::json(['deleted' => true]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/users/me/application-passwords/new*', $delete);

        $result = $this->wordPress->applicationPasswords()->delete('me', 'new');

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
        $index = new TestResponseSequence();
        $index->push(TestResponse::json([
            'name' => 'Test',
            'namespaces' => ['wp/v2'],
            'routes' => ['/wp/v2' => ['namespace' => 'wp/v2']],
        ]));
        $index->push(TestResponse::json([
            'name' => 'Test',
            'namespaces' => ['wp/v2'],
            'routes' => ['/wp/v2' => ['namespace' => 'wp/v2']],
        ]));
        $this->httpFakes()->respond('GET', '*', $index);

        self::assertSame('Test', $this->wordPress->discovery()->index()['name']);
        self::assertSame(['/wp/v2' => ['namespace' => 'wp/v2']], $this->wordPress->discovery()->routes());
        self::assertSame('', $this->lastRequest()->getUri()->getQuery());
    }

    public function testDiscoveryRoutesWithoutRoutesKey(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['name' => 'Test']));
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

        $created = $this->wordPress->custom()->post('my-plugin/v1/items', ['name' => 'Example']);

        self::assertSame(['id' => 1], $created);
        $this->assertJsonBody($this->lastRequest(), ['name' => 'Example']);

        $patch = new TestResponseSequence();
        $patch->push(TestResponse::json(['id' => 1, 'name' => 'Renamed']));
        $this->httpFakes()->respond('PATCH', '*my-plugin/v1/items*', $patch);

        $updated = $this->wordPress->custom()->patch('my-plugin/v1/items/1', ['name' => 'Renamed']);

        self::assertSame(['id' => 1, 'name' => 'Renamed'], $updated);
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
        $list = new TestResponseSequence();
        $list->push(TestResponse::make(200, [], json_encode([
            ['id' => 1, 'title' => ['rendered' => 'Rev']],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/posts/9/revisions*', $list);

        $revisions = $this->wordPress->revisions()->posts(9)->list();

        self::assertSame([['id' => 1, 'title' => ['rendered' => 'Rev']]], $revisions);
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
