<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class CreateEphemeralPostData
{
    public function __construct(
        public readonly string $userId,
        public readonly string $channelId,
        public readonly string $message,
    ) {
        if (empty($this->userId)) {
            throw new InvalidArgumentException('user_id must not be empty.');
        }

        if (empty($this->channelId)) {
            throw new InvalidArgumentException('channel_id must not be empty.');
        }

        if (empty($this->message)) {
            throw new InvalidArgumentException('message must not be empty.');
        }
    }

    /** @return array<string, mixed> */
    public function toBody(): array
    {
        return [
            'user_id' => $this->userId,
            'post'    => [
                'channel_id' => $this->channelId,
                'message'    => $this->message,
            ],
        ];
    }
}
