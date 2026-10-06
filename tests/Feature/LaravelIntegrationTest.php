<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Laravel\TimeMessengerServiceProvider;
use Fenestron\TimeMessenger\Resources\UsersResource;
use Fenestron\TimeMessenger\Tests\TestCase;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class LaravelIntegrationTest extends TestCase
{
    public function test_provider_and_alias_are_declared_for_auto_discovery(): void
    {
        $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true);

        $this->assertSame([TimeMessengerServiceProvider::class], $composer['extra']['laravel']['providers']);
        $this->assertSame(['Time' => Time::class], $composer['extra']['laravel']['aliases']);
    }

    public function test_provider_is_registered(): void
    {
        $this->assertInstanceOf(TimeMessengerServiceProvider::class, $this->app->getProvider(TimeMessengerServiceProvider::class));
    }

    public function test_config_is_merged_with_defaults(): void
    {
        $this->assertSame(90, config('time-messenger.file_download_timeout'));
        $this->assertSame('https://time.test', config('time-messenger.base_url'));
    }

    public function test_facade_resolves_a_singleton_client(): void
    {
        $this->assertInstanceOf(TimeClient::class, Time::getFacadeRoot());
        $this->assertSame($this->app->make(TimeClient::class), $this->app->make(TimeClient::class));
        $this->assertInstanceOf(UsersResource::class, Time::users());
        $this->assertSame('bot-user-id', $this->app->make(TimeClient::class)->userId);
    }

    public function test_http_fake_intercepts_client_requests(): void
    {
        $this->app->make(TimeClient::class);

        Http::fake(['https://time.test/api/v4/users/me' => Http::response(['id' => 'self'])]);

        $this->assertSame('self', Time::users()->get('me')['id']);

        Http::assertSent(fn (Request $request): bool => $request->header('Authorization')[0] === 'Bearer test-token');
    }

    public function test_config_can_be_published(): void
    {
        $paths = TimeMessengerServiceProvider::pathsToPublish(TimeMessengerServiceProvider::class, 'time-messenger-config');

        $this->assertSame([realpath(__DIR__.'/../../config/time-messenger.php')], array_map('realpath', array_keys($paths)));
        $this->assertSame([config_path('time-messenger.php')], array_values($paths));
    }

    public function test_missing_base_url_fails_on_first_use(): void
    {
        config()->set('time-messenger.base_url', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Time Messenger is not configured: set TIME_BASE_URL and TIME_TOKEN');

        Time::users();
    }

    public function test_missing_token_fails_on_first_use(): void
    {
        config()->set('time-messenger.token', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Time Messenger is not configured: set TIME_BASE_URL and TIME_TOKEN');

        $this->app->make(TimeClient::class);
    }
}
