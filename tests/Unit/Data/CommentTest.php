<?php

declare(strict_types=1);

namespace JOOservices\WordPress\Sdk\Tests\Unit\Data;

use JOOservices\WordPress\Sdk\Data\Comment;
use JOOservices\WordPress\Sdk\Tests\TestCase;

final class CommentTest extends TestCase
{
    public function testHydrates(): void
    {
        $authorName = $this->faker->name();
        $content = sprintf('<p>%s</p>', $this->faker->sentence());
        $avatarUrl = $this->faker->imageUrl();
        $comment = Comment::from([
            'id' => '1', 'post' => '42', 'parent' => '0', 'author' => '2',
            'author_name' => $authorName, 'author_url' => '', 'content' => ['rendered' => $content],
            'status' => 'approve', 'type' => 'comment', 'author_avatar_urls' => ['96' => $avatarUrl],
        ]);

        self::assertSame(1, $comment->id);
        self::assertSame(42, $comment->post);
        self::assertSame($authorName, $comment->author_name);
        self::assertSame($content, $comment->content?->rendered);
        self::assertSame($avatarUrl, $comment->author_avatar_urls['96'] ?? null);
    }

    public function testHydratesEditContextAuthorFields(): void
    {
        $email = $this->faker->email();
        $comment = Comment::from([
            'id' => 1,
            'post' => 42,
            'author_name' => $this->faker->name(),
            'author_email' => $email,
            'author_ip' => '127.0.0.1',
            'author_user_agent' => 'PHPUnit',
            'content' => ['rendered' => '<p>Hi</p>'],
        ]);

        self::assertSame($email, $comment->author_email);
        self::assertSame('127.0.0.1', $comment->author_ip);
        self::assertSame('PHPUnit', $comment->author_user_agent);
    }

    public function testPreservesEmbeddedRestMetadata(): void
    {
        $links = ['self' => [['href' => $this->faker->url()]]];
        $embedded = ['up' => [['id' => $this->faker->numberBetween(1)]]];
        $comment = Comment::from(['_links' => $links, '_embedded' => $embedded]);

        self::assertSame($links, $comment->_links);
        self::assertSame($embedded, $comment->_embedded);
    }
}
