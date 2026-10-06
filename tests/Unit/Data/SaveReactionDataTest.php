<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\SaveReactionData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SaveReactionDataTest extends TestCase
{
    public function test_to_body_uses_own_user_id(): void
    {
        $data = new SaveReactionData(postId: 'post123', emojiName: 'thumbsup', userId: 'user123', createAt: 1000);

        $this->assertSame([
            'user_id'    => 'user123',
            'post_id'    => 'post123',
            'emoji_name' => 'thumbsup',
            'create_at'  => 1000,
        ], $data->toBody('default-user'));
    }

    public function test_to_body_falls_back_to_default_user_id(): void
    {
        $data = new SaveReactionData(postId: 'post123', emojiName: 'thumbsup');

        $this->assertSame([
            'user_id'    => 'default-user',
            'post_id'    => 'post123',
            'emoji_name' => 'thumbsup',
        ], $data->toBody('default-user'));
    }

    public function test_to_body_throws_without_any_user_id(): void
    {
        $data = new SaveReactionData(postId: 'post123', emojiName: 'thumbsup');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id must be set');

        $data->toBody();
    }

    public function test_to_body_throws_with_empty_default_user_id(): void
    {
        $data = new SaveReactionData(postId: 'post123', emojiName: 'thumbsup');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id must be set');

        $data->toBody('');
    }

    public function test_throws_when_post_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('post_id must not be empty');

        new SaveReactionData(postId: '', emojiName: 'thumbsup');
    }

    public function test_throws_when_emoji_name_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('emoji_name must not be empty');

        new SaveReactionData(postId: 'post123', emojiName: '');
    }
}
