<?php

namespace Fenestron\TimeMessenger\Laravel;

use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class TimeMessengerServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__.'/../../config/time-messenger.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'time-messenger');

        /*
         * The application's HTTP factory is the same singleton that `Http::fake()` swaps,
         * so faking works for the client without any extra setup.
         */
        $this->app->singleton(TimeClient::class, function (Application $app): TimeClient {
            /** @var array{token: ?string, base_url: ?string, user_id: ?string, file_download_timeout: int|string|null} $config */
            $config = $app->make('config')->get('time-messenger');

            if (empty($config['base_url']) || empty($config['token'])) {
                throw new InvalidArgumentException('Time Messenger is not configured: set TIME_BASE_URL and TIME_TOKEN');
            }

            return new TimeClient(
                baseUrl: $config['base_url'],
                token: $config['token'],
                userId: $config['user_id'] ?? null,
                fileDownloadTimeout: (int) ($config['file_download_timeout'] ?? 90),
                http: $app->make(Factory::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_PATH => $this->app->configPath('time-messenger.php'),
            ], 'time-messenger-config');
        }
    }
}
