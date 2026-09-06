<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Services;

use InvalidArgumentException;
use JOOservices\Client\Testing\TestResponse;
use JOOservices\Client\Testing\TestResponseSequence;
use JOOservices\WordPress\Sdk\Data\Query\ListTermsQuery;
use JOOservices\WordPress\Sdk\Tests\TestCase;
use JOOservices\WordPress\Sdk\WordPressService;

final class TermsServiceTest extends TestCase
{
    private WordPressService $wordPress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wordPress = $this->wordPress();
    }

    public function testDeleteDefaultsToForceAndSendsForceTrue(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::json(['deleted' => true, 'previous' => ['id' => 6, 'name' => 'Tech']]));
        $this->httpFakes()->respond('DELETE', '*wp/v2/portfolio/6*', $sequence);

        $deleted = $this->wordPress->terms('portfolio')->delete(6);

        self::assertSame(6, $deleted->id);
        $this->assertQuery($this->lastRequest(), ['force' => 'true']);
    }

    public function testDeleteRejectsExplicitFalse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->wordPress->terms('portfolio')->delete(6, false);
    }

    public function testHierarchicalTaxonomyDropsOffset(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 9, 'name' => 'Finance'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/departments*', $sequence);

        $this->wordPress->terms('departments', hierarchical: true)
            ->list(new ListTermsQuery(page: 2, offset: 5));

        parse_str($this->lastRequest()->getUri()->getQuery(), $query);
        self::assertArrayNotHasKey('offset', $query);
        self::assertSame('2', $query['page']);
    }

    public function testNonHierarchicalTaxonomyKeepsOffset(): void
    {
        $sequence = new TestResponseSequence();
        $sequence->push(TestResponse::make(200, [
            'X-WP-Total' => '1',
            'X-WP-TotalPages' => '1',
        ], json_encode([
            ['id' => 9, 'name' => 'Finance'],
        ], JSON_THROW_ON_ERROR)));
        $this->httpFakes()->respond('GET', '*wp/v2/portfolio*', $sequence);

        $this->wordPress->terms('portfolio')
            ->list(new ListTermsQuery(offset: 5));

        parse_str($this->lastRequest()->getUri()->getQuery(), $query);
        self::assertSame('5', $query['offset']);
    }
}
