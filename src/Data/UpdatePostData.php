<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class UpdatePostData
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $message = null,
        public readonly ?bool $isPinned = null,
    ) {
        if (empty($this->id)) {
            throw new InvalidArgumentException('id must not be empty.');
        }

        if ($this->message === null && $this->isPinned === null) {
            throw new InvalidArgumentException('Either message or is_pinned must be provided.');
        }
    }

    /** @return array<string, mixed> */
    public function toBody(): array
    {
        return array_filter([
            'id' => $this->id,
            'message' => $this->message,
            'is_pinned' => $this->isPinned,
        ], fn (mixed $value): bool => $value !== null);
    }
}
