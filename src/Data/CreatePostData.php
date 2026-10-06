<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class CreatePostData
{
    /**
     * @param  string[]|null  $fileIds
     */
    public function __construct(
        public readonly string $message,
        public readonly ?string $channelId = null,
        public readonly ?string $peer = null,
        public readonly ?string $rootId = null,
        public readonly ?array $fileIds = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?bool $setOnline = null,
    ) {
        if (empty($this->message)) {
            throw new InvalidArgumentException('message must not be empty.');
        }

        if ($this->channelId === null && $this->peer === null) {
            throw new InvalidArgumentException('Either channel_id or peer must be provided.');
        }

        if ($this->fileIds !== null && count($this->fileIds) > 5) {
            throw new InvalidArgumentException('A post can have at most 5 files.');
        }
    }

    /** @return array<string, mixed> */
    public function toBody(): array
    {
        return array_filter([
            'idempotency_key' => $this->idempotencyKey,
            'channel_id' => $this->channelId,
            'peer' => $this->peer,
            'message' => $this->message,
            'root_id' => $this->rootId,
            'file_ids' => $this->fileIds,
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return array_filter([
            'set_online' => $this->setOnline,
        ], fn (mixed $value): bool => $value !== null);
    }
}
