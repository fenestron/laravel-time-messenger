<?php

namespace Fenestron\TimeMessenger\Tests;

use Fenestron\TimeMessenger\Laravel\TimeMessengerServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [TimeMessengerServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('time-messenger.base_url', 'https://time.test');
        $app['config']->set('time-messenger.token', 'test-token');
        $app['config']->set('time-messenger.user_id', 'bot-user-id');
    }
}
