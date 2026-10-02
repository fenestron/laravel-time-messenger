# Laravel Time Messenger

> **Work in progress.** The package is not ready for use yet. The first release will be `v0.1.0`.

Laravel client for the [Time](https://time-messenger.ru) messenger API.

## Planned features

- Fluent facade for the REST API: posts, ephemeral posts, reactions, files, channels and users.
- Built on Laravel's HTTP client, so `Http::fake()` works in your tests.
- Optional WebSocket listener for real-time events with pluggable event handlers. Requires `amphp/websocket-client`, which is not installed by default.

## Requirements

- PHP 8.4+
- Laravel 12 or 13

## Installation

```bash
composer require fenestron/laravel-time-messenger
```

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
