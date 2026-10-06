<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class DeleteReactionData
{
    public function __construct(
        public readonly string $postId,
        public readonly string $emojiName,
        public readonly ?string $userId = null,
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
     *
     * @throws InvalidArgumentException When neither `userId` nor `$defaultUserId` is set.
     */
    public function toPath(?string $defaultUserId = null): string
    {
        $userId = $this->userId ?? $defaultUserId;

        if ($userId === null || $userId === '') {
            throw new InvalidArgumentException('user_id must be set either in the reaction data or in the client.');
        }

        return sprintf(
            '/api/v4/users/%s/posts/%s/reactions/%s',
            rawurlencode($userId),
            rawurlencode($this->postId),
            rawurlencode($this->emojiName),
        );
    }
}
