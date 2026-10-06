<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\UploadFileData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UploadFileDataTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $data = new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        );

        $this->assertSame('binary-data', $data->contents);
        $this->assertSame('file.txt', $data->filename);
        $this->assertSame('chan123', $data->channelId);
        $this->assertNull($data->clientId);
    }

    public function test_creates_with_optional_client_id(): void
    {
        $data = new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
            clientId: 'client-abc',
        );

        $this->assertSame('client-abc', $data->clientId);
    }

    public function test_throws_when_contents_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contents must not be empty');

        new UploadFileData(contents: '', filename: 'file.txt', channelId: 'chan123');
    }

    public function test_throws_when_filename_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('filename must not be empty');

        new UploadFileData(contents: 'binary-data', filename: '', channelId: 'chan123');
    }

    public function test_throws_when_channel_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('channelId must not be empty');

        new UploadFileData(contents: 'binary-data', filename: 'file.txt', channelId: '');
    }
}
