<?php

namespace Fenestron\TimeMessenger;

class DirectChannel
{
    /**
     * A direct channel name is two user ids (exactly 26 characters of the id alphabet each)
     * joined with "__", and nothing else.
     */
    public static function isDirectChannelName(string $channelName): bool
    {
        return preg_match('/^[a-z0-9]{26}__[a-z0-9]{26}$/', $channelName) === 1;
    }
}
