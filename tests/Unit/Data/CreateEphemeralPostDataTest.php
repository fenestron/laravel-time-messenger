<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\CreateEphemeralPostData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreateEphemeralPostDataTest extends TestCase
{
    public function test_to_body_builds_nested_structure(): void
    {
        $data = new CreateEphemeralPostData(
            userId: 'user123',
            channelId: 'chan123',
            message: 'Hello',
        );

        $this->assertSame([
            'user_id' => 'user123',
            'post'    => [
                'channel_id' => 'chan123',
                'message'    => 'Hello',
            ],
        ], $data->toBody());
    }

    public function test_throws_when_user_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('user_id must not be empty');

        new CreateEphemeralPostData(userId: '', channelId: 'chan123', message: 'Hello');
    }

    public function test_throws_when_channel_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('channel_id must not be empty');

        new CreateEphemeralPostData(userId: 'user123', channelId: '', message: 'Hello');
    }

    public function test_throws_when_message_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message must not be empty');

        new CreateEphemeralPostData(userId: 'user123', channelId: 'chan123', message: '');
    }
}
