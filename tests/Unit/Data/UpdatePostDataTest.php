<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\UpdatePostData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UpdatePostDataTest extends TestCase
{
    public function test_creates_with_message(): void
    {
        $data = new UpdatePostData(id: 'post123', message: 'Updated text');

        $this->assertSame('post123', $data->id);
        $this->assertSame('Updated text', $data->message);
    }

    public function test_creates_with_is_pinned(): void
    {
        $data = new UpdatePostData(id: 'post123', isPinned: true);

        $this->assertTrue($data->isPinned);
    }

    public function test_throws_when_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('id must not be empty');

        new UpdatePostData(id: '', message: 'Hello');
    }

    public function test_throws_when_neither_message_nor_is_pinned_provided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either message or is_pinned must be provided');

        new UpdatePostData(id: 'post123');
    }

    public function test_to_body_omits_null_fields(): void
    {
        $data = new UpdatePostData(id: 'post123', message: 'Hello');

        $body = $data->toBody();

        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayNotHasKey('is_pinned', $body);
    }

    public function test_to_body_includes_all_provided_fields(): void
    {
        $data = new UpdatePostData(id: 'post123', message: 'Hello', isPinned: true);

        $body = $data->toBody();

        $this->assertSame('post123', $body['id']);
        $this->assertSame('Hello', $body['message']);
        $this->assertTrue($body['is_pinned']);
    }
}
