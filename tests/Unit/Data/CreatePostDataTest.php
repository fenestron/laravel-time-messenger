<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\CreatePostData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreatePostDataTest extends TestCase
{
    public function test_creates_with_channel_id(): void
    {
        $data = new CreatePostData(message: 'Hello', channelId: 'chan123');

        $this->assertSame('Hello', $data->message);
        $this->assertSame('chan123', $data->channelId);
    }

    public function test_creates_with_peer(): void
    {
        $data = new CreatePostData(message: 'Hello', peer: '@user');

        $this->assertSame('@user', $data->peer);
    }

    public function test_throws_when_message_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message must not be empty');

        new CreatePostData(message: '', channelId: 'chan123');
    }

    public function test_throws_when_neither_channel_id_nor_peer_provided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either channel_id or peer must be provided');

        new CreatePostData(message: 'Hello');
    }

    public function test_throws_when_more_than_5_file_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at most 5 files');

        new CreatePostData(
            message: 'Hello',
            channelId: 'chan123',
            fileIds: ['a', 'b', 'c', 'd', 'e', 'f'],
        );
    }

    public function test_to_body_omits_null_fields(): void
    {
        $data = new CreatePostData(message: 'Hello', channelId: 'chan123');

        $body = $data->toBody();

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('channel_id', $body);
        $this->assertArrayNotHasKey('root_id', $body);
        $this->assertArrayNotHasKey('file_ids', $body);
        $this->assertArrayNotHasKey('peer', $body);
        $this->assertArrayNotHasKey('idempotency_key', $body);
    }

    public function test_to_body_includes_all_provided_fields(): void
    {
        $data = new CreatePostData(
            message: 'Hello',
            channelId: 'chan123',
            rootId: 'root1',
            fileIds: ['f1', 'f2'],
            idempotencyKey: 'idem-key',
        );

        $body = $data->toBody();

        $this->assertSame('chan123', $body['channel_id']);
        $this->assertSame('root1', $body['root_id']);
        $this->assertSame(['f1', 'f2'], $body['file_ids']);
        $this->assertSame('idem-key', $body['idempotency_key']);
    }

    public function test_to_query_omits_null_set_online(): void
    {
        $data = new CreatePostData(message: 'Hello', channelId: 'chan123');

        $this->assertSame([], $data->toQuery());
    }

    public function test_to_query_includes_set_online_when_provided(): void
    {
        $data = new CreatePostData(message: 'Hello', channelId: 'chan123', setOnline: true);

        $this->assertSame(['set_online' => true], $data->toQuery());
    }
}
