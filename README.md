# Laravel Time Messenger

Client for the [Time](https://time-messenger.ru) messenger REST API (`/api/v4`): posts, ephemeral posts, reactions, files, channels and users.

The client works in any PHP project. Inside Laravel it adds auto-discovery, a `Time` facade and a config file, and it uses the application's HTTP client, so `Http::fake()` works in your tests.

## Requirements

- PHP 8.4+
- `illuminate/http` and `illuminate/support` 12 or 13 (installed automatically)

## Installation

```bash
composer require fenestron/laravel-time-messenger
```

Two settings are required: the base URL of your Time installation and a token.

| Variable | Required | Meaning |
|---|---|---|
| `TIME_BASE_URL` | yes | Base URL of the installation, e.g. `https://time.example.com` |
| `TIME_TOKEN` | yes | Personal access token or bot token |
| `TIME_USER_ID` | no | Default user for reactions, see [Reactions](#reactions) |
| `TIME_FILE_DOWNLOAD_TIMEOUT` | no | Seconds for downloading a file, `90` by default, see [File download timeout](#file-download-timeout) |

## Usage without Laravel

Create the client with plain values:

```php
use Fenestron\TimeMessenger\Data\CreatePostData;
use Fenestron\TimeMessenger\TimeClient;

$time = new TimeClient(
    baseUrl: getenv('TIME_BASE_URL'),
    token: getenv('TIME_TOKEN'),
    userId: getenv('TIME_USER_ID') ?: null,  // optional
    fileDownloadTimeout: 90,                  // optional
);

$time->posts()->create(new CreatePostData(message: 'Hello', channelId: $channelId));
```

If you prefer the static facade, hand it your client once. No container is needed:

```php
use Fenestron\TimeMessenger\Laravel\Facades\Time;

Time::swap($time);

Time::users()->get('me');
```

## Usage with Laravel

The service provider and the `Time` alias are registered by package auto-discovery. Set `TIME_BASE_URL` and `TIME_TOKEN` in `.env` and use the facade or inject `TimeClient`:

```php
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\TimeClient;

Time::posts()->get($postId);

public function handle(TimeClient $time): void
{
    $time->channels()->get($channelId);
}
```

The application boots without the variables. The first call to the client throws `InvalidArgumentException: Time Messenger is not configured: set TIME_BASE_URL and TIME_TOKEN`.

To change the config file, publish it:

```bash
php artisan vendor:publish --tag=time-messenger-config
```

### Testing your application

The client sends requests through the application's `Http` factory, so the usual fakes apply:

```php
use Fenestron\TimeMessenger\Data\CreatePostData;
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

Http::fake([
    config('time-messenger.base_url').'/api/v4/posts' => Http::response(['id' => 'post1'], 201),
]);

Time::posts()->create(new CreatePostData(message: 'Hello', channelId: 'chan1'));

Http::assertSent(fn (Request $request) => $request['message'] === 'Hello');
```

## Resources

The examples use the facade. With a plain client, replace `Time::` with `$time->`.

### Posts

```php
use Fenestron\TimeMessenger\Data\CreateEphemeralPostData;
use Fenestron\TimeMessenger\Data\CreatePostData;
use Fenestron\TimeMessenger\Data\GetChannelPostsData;
use Fenestron\TimeMessenger\Data\GetPostsByIdsData;
use Fenestron\TimeMessenger\Data\UpdatePostData;

// To a channel, or to a person by `peer`; up to 5 files; reply with `rootId`.
$post = Time::posts()->create(new CreatePostData(
    message: 'Build is green',
    channelId: $channelId,
    rootId: $threadId,
    fileIds: [$fileId],
    idempotencyKey: 'build-1234',
));

Time::posts()->createEphemeral(new CreateEphemeralPostData(userId: $userId, channelId: $channelId, message: 'Only you can see this'));
Time::posts()->get($postId);
Time::posts()->forChannel($channelId, new GetChannelPostsData(page: 0, perPage: 60));
Time::posts()->getByIds(new GetPostsByIdsData([$postId, $otherPostId]));
Time::posts()->update(new UpdatePostData(id: $postId, message: 'Edited'));
Time::posts()->delete($postId);
```

### Reactions

```php
use Fenestron\TimeMessenger\Data\DeleteReactionData;
use Fenestron\TimeMessenger\Data\SaveReactionData;

Time::reactions()->save(new SaveReactionData(postId: $postId, emojiName: 'white_check_mark'));
Time::reactions()->delete(new DeleteReactionData(postId: $postId, emojiName: 'white_check_mark'));
```

A reaction is put on behalf of `userId` from the data object or, when it is omitted, of the client's default user (`TIME_USER_ID`, or the `userId` constructor argument). If neither is set, an `InvalidArgumentException` is thrown before any request is sent.

### Files

```php
use Fenestron\TimeMessenger\Data\UploadFileData;

$uploaded = Time::files()->upload(
    new UploadFileData(contents: $bytes, filename: 'report.pdf', channelId: $channelId),
);
$fileId = $uploaded['file_infos'][0]['id'];

Time::files()->info($fileId);           // metadata: name, size, mime_type, ...
$contents = Time::files()->get($fileId); // raw bytes
```

Several files can be uploaded in one request: `upload($first, $second)`. A file id is bound to the channel it was uploaded to.

#### Post attachments

`TimePostAttachments` reads attachment references from a post, either from a websocket `posted` frame or from a REST response, without any requests:

```php
use Fenestron\TimeMessenger\TimePostAttachments;

$refs = TimePostAttachments::refsFromPost($post); // list of ['id' => ..., 'name' => ..., 'size' => ...]
```

A post that carries bare `file_ids` instead of `metadata.files` gives refs with an empty name and a zero size. Fill them in with one `files()->info()` per file:

```php
$refs = Time::attachments()->resolveMissing($refs, sizeRequired: true);
```

If Time fails to answer for a file, its id is used as the name and the size stays zero.

### Channels

```php
Time::channels()->get($channelId);
Time::channels()->forUser($userId); // all channels of the user across teams
```

### Users

```php
Time::users()->get($userId);
Time::users()->get('me'); // the owner of the token
```

## Errors

Every method throws one of two exceptions, both extending `RuntimeException`:

- `Fenestron\TimeMessenger\Exceptions\TimeApiException`: Time responded with a 4xx or 5xx status.
- `Fenestron\TimeMessenger\Exceptions\TimeConnectionException`: Time could not be reached (DNS, TLS, connect or read timeout). The original exception is available through `getPrevious()`.

Data objects throw `InvalidArgumentException` for invalid input, before any request is sent.

```php
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;

try {
    Time::posts()->get($postId);
} catch (TimeApiException $exception) {
    $exception->status();          // 404
    $exception->body();            // raw response body
    $exception->json('message');   // a value from the JSON body, by dot-notation key
} catch (TimeConnectionException $exception) {
    // retry later
}
```

## File download timeout

JSON calls time out after 30 seconds: for them, 30 seconds already means something is wrong. Downloading a file is a different operation: it moves megabytes over a channel whose speed varies a lot. Measurements against one installation showed the same 10 MB file downloaded in 28 seconds (about 350 KB/s) in one run, while another run delivered only 4.3 MB in 30 seconds (about 170 KB/s). With the shared limit, files close to 10 MB failed every other time.

That is why `files()->get()` has its own limit, `TIME_FILE_DOWNLOAD_TIMEOUT`, 90 seconds by default. At the slowest observed speed of 170 KB/s it covers about 15 MB, so 10 MB files fit with a margin. If you download files inside a queued job or another process with its own time limit, keep this value well below that limit, or the process will be killed in the middle of a download.

## Testing

```bash
composer install
composer test
composer format
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
