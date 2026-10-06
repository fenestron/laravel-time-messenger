<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\DeleteReactionData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DeleteReactionDataTest extends TestCase
{
    public function test_to_path_uses_own_user_id(): void
    {
        $data = new DeleteReactionData(postId: 'post123', emojiName: 'thumbsup', userId: 'user123');

        $this->assertSame('/api/v4/users/user123/posts/post123/reactions/thumbsup', $data->toPath('default-user'));
    }

    public function test_to_path_falls_back_to_default_user_id(): void
    {
        $data = new DeleteReactionData(postId: 'post123', emojiName: 'thumbsup');

        $this->assertSame('/api/v4/users/default-user/posts/post123/reactions/thumbsup', $data->toPath('default-user'));
    }

    public function test_to_path_encodes_segments(): void
    {
        $data = new DeleteReactionData(postId: 'post/1', emojiName: '+1', userId: 'user 1');

        $this->assertSame('/api/v4/users/user%201/posts/post%2F1/reactions/%2B1', $data->toPath());
    }

    public function test_to_path_throws_without_any_user_id(): void
    {
        $data = new DeleteReactionData(postId: 'post123', emojiName: 'thumbsup');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id must be set');

        $data->toPath();
    }

    public function test_to_path_throws_with_empty_default_user_id(): void
    {
        $data = new DeleteReactionData(postId: 'post123', emojiName: 'thumbsup');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id must be set');

        $data->toPath('');
    }

    public function test_throws_when_post_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('post_id must not be empty');

        new DeleteReactionData(postId: '', emojiName: 'thumbsup');
    }

    public function test_throws_when_emoji_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('emoji_name must not be empty');

        new DeleteReactionData(postId: 'post123', emojiName: '');
    }
}
