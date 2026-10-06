<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class UploadFileData
{
    public function __construct(
        public readonly string $contents,
        public readonly string $filename,
        public readonly string $channelId,
        public readonly ?string $clientId = null,
    ) {
        if (empty($this->contents)) {
            throw new InvalidArgumentException('contents must not be empty.');
        }

        if (empty($this->filename)) {
            throw new InvalidArgumentException('filename must not be empty.');
        }

        if (empty($this->channelId)) {
            throw new InvalidArgumentException('channelId must not be empty.');
        }
    }
}
