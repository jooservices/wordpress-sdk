<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Media;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class MediaTest extends TestCase
{
    public function testHydratesRenderedFields(): void
    {
        $title = $this->faker->sentence(2);
        $altText = $this->faker->words(2, true);
        $sourceUrl = $this->faker->url();
        $media = Media::from([
            'id' => '3', 'title' => ['rendered' => $title],
            'caption' => ['rendered' => sprintf('<p>%s</p>', $this->faker->sentence())],
            'description' => ['rendered' => sprintf('<p>%s</p>', $this->faker->sentence())],
            'alt_text' => $altText, 'media_type' => 'image', 'mime_type' => 'image/png',
            'media_details' => ['width' => 800], 'author' => '1', 'source_url' => $sourceUrl,
        ]);

        self::assertSame(3, $media->id);
        self::assertSame($title, $media->title?->rendered);
        self::assertSame($altText, $media->alt_text);
        self::assertSame(['width' => 800], $media->media_details);
        self::assertSame($sourceUrl, $media->source_url);
    }

    public function testHydratesAttachmentFields(): void
    {
        $media = Media::from([
            'id' => '3',
            'alt_text' => $this->faker->words(2, true),
            'mime_type' => 'image/png',
            'source_url' => $this->faker->url(),
            'post' => 42,
            'date_gmt' => '2026-08-29T10:00:00',
            'modified' => '2026-08-29T11:00:00',
            'comment_status' => 'open',
            'meta' => ['camera' => 'x'],
        ]);

        self::assertSame(3, $media->id);
        self::assertSame(42, $media->post);
        self::assertSame('open', $media->comment_status);
        self::assertSame(['camera' => 'x'], $media->meta);
    }

    public function testHydratesNullDateGmt(): void
    {
        $media = Media::from(['date_gmt' => null]);

        self::assertNull($media->date_gmt);
    }
}
