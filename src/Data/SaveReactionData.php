<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class SaveReactionData
{
    public function __construct(
        public readonly string $postId,
        public readonly string $emojiName,
        public readonly ?string $userId = null,
        public readonly ?int $createAt = null,
    ) {
        if (empty($this->postId)) {
            throw new InvalidArgumentException('post_id must not be empty.');
        }

        if (empty($this->emojiName)) {
            throw new InvalidArgumentException('emoji_name must not be empty.');
        }
    }

    /**
     * @param  string|null  $defaultUserId  Used when the data object has no `userId`.
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException When neither `userId` nor `$defaultUserId` is set.
     */
    public function toBody(?string $defaultUserId = null): array
    {
        $userId = $this->userId ?? $defaultUserId;

        if ($userId === null || $userId === '') {
            throw new InvalidArgumentException('user_id must be set either in the reaction data or in the client.');
        }

        return array_filter([
            'user_id' => $userId,
            'post_id' => $this->postId,
            'emoji_name' => $this->emojiName,
            'create_at' => $this->createAt,
        ], fn (mixed $value): bool => $value !== null);
    }
}
