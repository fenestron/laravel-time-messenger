<?php

namespace Fenestron\TimeMessenger\Laravel\Facades;

use Fenestron\TimeMessenger\Resources\ChannelsResource;
use Fenestron\TimeMessenger\Resources\FilesResource;
use Fenestron\TimeMessenger\Resources\PostsResource;
use Fenestron\TimeMessenger\Resources\ReactionsResource;
use Fenestron\TimeMessenger\Resources\UsersResource;
use Fenestron\TimeMessenger\TimeClient;
use Fenestron\TimeMessenger\TimePostAttachments;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PostsResource posts()
 * @method static ReactionsResource reactions()
 * @method static UsersResource users()
 * @method static FilesResource files()
 * @method static ChannelsResource channels()
 * @method static TimePostAttachments attachments()
 *
 * @see TimeClient
 */
class Time extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TimeClient::class;
    }
}
