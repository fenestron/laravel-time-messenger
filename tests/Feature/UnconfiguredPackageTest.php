<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Illuminate\Foundation\Application;
use InvalidArgumentException;

/**
 * A fresh application with the package installed and no `TIME_*` variables set.
 */
class UnconfiguredPackageTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
    }

    public function test_application_boots_without_configuration(): void
    {
        $this->assertTrue($this->app->isBooted());
        $this->assertNull(config('time-messenger.base_url'));
        $this->assertNull(config('time-messenger.token'));
        $this->assertSame(90, config('time-messenger.file_download_timeout'));
    }

    public function test_first_use_explains_what_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Time Messenger is not configured: set TIME_BASE_URL and TIME_TOKEN');

        Time::posts();
    }
}
