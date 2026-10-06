<?php

namespace Fenestron\TimeMessenger\Tests\Unit;

use Closure;
use Fenestron\TimeMessenger\Data\CreateEphemeralPostData;
use Fenestron\TimeMessenger\Data\CreatePostData;
use Fenestron\TimeMessenger\Data\DeleteReactionData;
use Fenestron\TimeMessenger\Data\GetPostsByIdsData;
use Fenestron\TimeMessenger\Data\SaveReactionData;
use Fenestron\TimeMessenger\Data\UpdatePostData;
use Fenestron\TimeMessenger\Data\UploadFileData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The core client without the Laravel container: a plain {@see Factory} is faked and passed
 * to {@see TimeClient} directly.
 */
class StandaloneClientTest extends TestCase
{
    private const string BASE_URL = 'https://time.test';

    private Factory $http;

    private TimeClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Factory;
        $this->http->preventStrayRequests();

        $this->client = new TimeClient(
            baseUrl: self::BASE_URL,
            token: 'secret',
            userId: 'bot-user',
            fileDownloadTimeout: 45,
            http: $this->http,
        );
    }

    public function test_constructs_with_scalars_only(): void
    {
        $client = new TimeClient('https://time.test', 'secret');

        $this->assertNull($client->userId);
        $this->assertSame(90, $client->fileDownloadTimeout);
    }

    public function test_posts(): void
    {
        $this->http->fake([self::BASE_URL.'/api/v4/posts' => Factory::response(['id' => 'post1'], 201)]);

        $response = $this->client->posts()->create(new CreatePostData(message: 'Hello', channelId: 'chan1'));

        $this->assertSame('post1', $response['id']);
        $this->http->assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/api/v4/posts'
            && $request->method() === 'POST'
            && $request->header('Authorization')[0] === 'Bearer secret'
            && $request['message'] === 'Hello'
            && $request['channel_id'] === 'chan1');
    }

    public function test_reactions_use_the_client_user_id_by_default(): void
    {
        $this->http->fake([
            self::BASE_URL.'/api/v4/reactions'                                => Factory::response(['post_id' => 'post1'], 201),
            self::BASE_URL.'/api/v4/users/bot-user/posts/post1/reactions/%2B1' => Factory::response(['status' => 'ok']),
        ]);

        $this->client->reactions()->save(new SaveReactionData(postId: 'post1', emojiName: 'heart'));
        $this->client->reactions()->delete(new DeleteReactionData(postId: 'post1', emojiName: '+1'));

        $this->http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request['user_id'] === 'bot-user');
        $this->http->assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === self::BASE_URL.'/api/v4/users/bot-user/posts/post1/reactions/%2B1');
    }

    public function test_reactions_without_any_user_id_are_rejected_before_the_request(): void
    {
        $client = new TimeClient(self::BASE_URL, 'secret', http: $this->http);

        try {
            $client->reactions()->save(new SaveReactionData(postId: 'post1', emojiName: 'heart'));
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }

        try {
            $client->reactions()->delete(new DeleteReactionData(postId: 'post1', emojiName: 'heart'));
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
        }

        $this->http->assertNothingSent();
    }

    public function test_users(): void
    {
        $this->http->fake([self::BASE_URL.'/api/v4/users/me' => Factory::response(['id' => 'self'])]);

        $this->assertSame('self', $this->client->users()->get('me')['id']);
    }

    public function test_channels(): void
    {
        $this->http->fake([
            self::BASE_URL.'/api/v4/channels/chan1'       => Factory::response(['id' => 'chan1']),
            self::BASE_URL.'/api/v4/users/user1/channels' => Factory::response([['id' => 'chan1']]),
        ]);

        $this->assertSame('chan1', $this->client->channels()->get('chan1')['id']);
        $this->assertSame('chan1', $this->client->channels()->forUser('user1')[0]['id']);
    }

    public function test_files(): void
    {
        $this->http->fake(function (Request $request, array $options) {
            return match ($request->url()) {
                self::BASE_URL.'/api/v4/files/file1'      => Factory::response('bytes:'.$options['timeout']),
                self::BASE_URL.'/api/v4/files/file1/info' => Factory::response(['id' => 'file1', 'size' => 5]),
                self::BASE_URL.'/api/v4/files'            => Factory::response(['file_infos' => [['id' => 'file2']]], 201),
            };
        });

        $this->assertSame('bytes:45', $this->client->files()->get('file1'));
        $this->assertSame(5, $this->client->files()->info('file1')['size']);
        $this->assertSame('file2', $this->client->files()->upload(
            new UploadFileData(contents: 'data', filename: 'a.txt', channelId: 'chan1'),
        )['file_infos'][0]['id']);
    }

    public function test_attachments_resolve_missing(): void
    {
        $this->http->fake([self::BASE_URL.'/api/v4/files/file1/info' => Factory::response(['name' => 'a.pdf', 'size' => 42])]);

        $refs = $this->client->attachments()->resolveMissing([['id' => 'file1', 'name' => '', 'size' => 0]], sizeRequired: true);

        $this->assertSame([['id' => 'file1', 'name' => 'a.pdf', 'size' => 42]], $refs);
    }

    public function test_attachments_survive_an_api_error_without_laravel(): void
    {
        $this->http->fake([self::BASE_URL.'/api/v4/files/file1/info' => Factory::response('', 500)]);

        $refs = $this->client->attachments()->resolveMissing([['id' => 'file1', 'name' => '', 'size' => 0]]);

        $this->assertSame([['id' => 'file1', 'name' => 'file1', 'size' => 0]], $refs);
    }

    /**
     * @param  Closure(TimeClient): mixed  $call
     */
    #[DataProvider('everyEndpoint')]
    public function test_http_errors_become_time_api_exception(Closure $call): void
    {
        $this->http->fake(fn (): mixed => Factory::response(['message' => 'Forbidden'], 403));

        try {
            $call($this->client);
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(403, $exception->status());
        }
    }

    /**
     * @param  Closure(TimeClient): mixed  $call
     */
    #[DataProvider('everyEndpoint')]
    public function test_connection_failures_become_time_connection_exception(Closure $call): void
    {
        $original = new ConnectionException('cURL error 28: Operation timed out');

        $this->http->fake(fn (): never => throw $original);

        try {
            $call($this->client);
            $this->fail('Expected TimeConnectionException');
        } catch (TimeConnectionException $exception) {
            $this->assertSame($original, $exception->getPrevious());
            $this->assertStringContainsString('cURL error 28', $exception->getMessage());
        }
    }

    /**
     * @return array<string, array{Closure(TimeClient): mixed}>
     */
    public static function everyEndpoint(): array
    {
        return [
            'posts.create'          => [fn (TimeClient $client): mixed => $client->posts()->create(new CreatePostData(message: 'Hi', channelId: 'chan1'))],
            'posts.createEphemeral' => [fn (TimeClient $client): mixed => $client->posts()->createEphemeral(new CreateEphemeralPostData(userId: 'u', channelId: 'c', message: 'Hi'))],
            'posts.get'             => [fn (TimeClient $client): mixed => $client->posts()->get('post1')],
            'posts.forChannel'      => [fn (TimeClient $client): mixed => $client->posts()->forChannel('chan1')],
            'posts.update'          => [fn (TimeClient $client): mixed => $client->posts()->update(new UpdatePostData(id: 'post1', message: 'Hi'))],
            'posts.delete'          => [fn (TimeClient $client): mixed => $client->posts()->delete('post1')],
            'posts.getByIds'        => [fn (TimeClient $client): mixed => $client->posts()->getByIds(new GetPostsByIdsData(['post1']))],
            'reactions.save'        => [fn (TimeClient $client): mixed => $client->reactions()->save(new SaveReactionData(postId: 'post1', emojiName: '+1'))],
            'reactions.delete'      => [fn (TimeClient $client): mixed => $client->reactions()->delete(new DeleteReactionData(postId: 'post1', emojiName: '+1'))],
            'users.get'             => [fn (TimeClient $client): mixed => $client->users()->get('me')],
            'channels.get'          => [fn (TimeClient $client): mixed => $client->channels()->get('chan1')],
            'channels.forUser'      => [fn (TimeClient $client): mixed => $client->channels()->forUser('user1')],
            'files.get'             => [fn (TimeClient $client): mixed => $client->files()->get('file1')],
            'files.info'            => [fn (TimeClient $client): mixed => $client->files()->info('file1')],
            'files.upload'          => [fn (TimeClient $client): mixed => $client->files()->upload(new UploadFileData(contents: 'data', filename: 'a.txt', channelId: 'chan1'))],
        ];
    }
}
