<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Data\CreateEphemeralPostData;
use Fenestron\TimeMessenger\Data\CreatePostData;
use Fenestron\TimeMessenger\Data\GetChannelPostsData;
use Fenestron\TimeMessenger\Data\GetPostsByIdsData;
use Fenestron\TimeMessenger\Data\UpdatePostData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class PostsResourceTest extends TestCase
{
    private string $timeUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeUrl = config('time-messenger.base_url');

        Http::preventStrayRequests();
    }

    public function test_create_post_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts" => Http::response(['id' => 'post123', 'message' => 'Hello'], 201),
        ]);

        $data = new CreatePostData(
            message: 'Hello',
            channelId: 'chan123',
        );

        $response = Time::posts()->create($data);

        $this->assertSame('post123', $response['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/posts"
                && $request->method() === 'POST'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ')
                && $request['message'] === 'Hello'
                && $request['channel_id'] === 'chan123';
        });
    }

    public function test_create_post_includes_set_online_query_param(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts*" => Http::response(['id' => 'post456'], 201),
        ]);

        $data = new CreatePostData(
            message: 'Hello',
            channelId: 'chan123',
            setOnline: true,
        );

        Time::posts()->create($data);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'set_online=1');
        });
    }

    public function test_create_post_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts" => Http::response(['message' => 'Bad Request'], 400),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->create(new CreatePostData(
            message: 'Hello',
            channelId: 'chan123',
        ));
    }

    public function test_create_post_exception_has_correct_status(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::posts()->create(new CreatePostData(
                message: 'Hello',
                channelId: 'chan123',
            ));
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }

    public function test_create_ephemeral_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/ephemeral" => Http::response(['id' => 'post123', 'message' => 'Hello'], 201),
        ]);

        $data = new CreateEphemeralPostData(
            userId: 'user123',
            channelId: 'chan123',
            message: 'Hello',
        );

        $response = Time::posts()->createEphemeral($data);

        $this->assertSame('post123', $response['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/posts/ephemeral"
                && $request->method() === 'POST'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ')
                && $request['user_id'] === 'user123'
                && $request['post'] === ['channel_id' => 'chan123', 'message' => 'Hello'];
        });
    }

    public function test_create_ephemeral_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/ephemeral" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->createEphemeral(new CreateEphemeralPostData(
            userId: 'user123',
            channelId: 'chan123',
            message: 'Hello',
        ));
    }

    public function test_uses_bearer_token_from_config(): void
    {
        $token = config('time-messenger.token');

        Http::fake([
            "{$this->timeUrl}/api/v4/posts" => Http::response(['id' => 'post789'], 201),
        ]);

        Time::posts()->create(new CreatePostData(
            message: 'Hello',
            channelId: 'chan123',
        ));

        Http::assertSent(function (Request $request) use ($token) {
            return $request->header('Authorization')[0] === "Bearer {$token}";
        });
    }

    public function test_get_post_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['id' => 'post123', 'message' => 'Hello'], 200),
        ]);

        $response = Time::posts()->get('post123');

        $this->assertSame('post123', $response['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/posts/post123"
                && $request->method() === 'GET'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_get_post_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->get('post123');
    }

    public function test_for_channel_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/channels/chan123/posts" => Http::response(
                ['order' => ['post1'], 'posts' => ['post1' => ['id' => 'post1']]],
                200,
            ),
        ]);

        $response = Time::posts()->forChannel('chan123');

        $this->assertSame(['post1'], $response['order']);
        $this->assertSame('post1', $response['posts']['post1']['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/channels/chan123/posts"
                && $request->method() === 'GET'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_for_channel_includes_query_parameters(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/channels/chan123/posts*" => Http::response(['order' => [], 'posts' => []], 200),
        ]);

        Time::posts()->forChannel('chan123', new GetChannelPostsData(page: 2, perPage: 30));

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'page=2')
                && str_contains($request->url(), 'per_page=30');
        });
    }

    public function test_for_channel_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/channels/chan123/posts" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->forChannel('chan123');
    }

    public function test_get_channel_posts_data_rejects_since_with_conflicting_params(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GetChannelPostsData(since: 1000, before: 'post1');
    }

    public function test_update_post_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['id' => 'post123', 'message' => 'Updated text'], 200),
        ]);

        $data = new UpdatePostData(id: 'post123', message: 'Updated text');

        $response = Time::posts()->update($data);

        $this->assertSame('post123', $response['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/posts/post123"
                && $request->method() === 'PUT'
                && $request['id'] === 'post123'
                && $request['message'] === 'Updated text';
        });
    }

    public function test_update_post_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->update(new UpdatePostData(id: 'post123', message: 'Hello'));
    }

    public function test_delete_post_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['status' => 'ok'], 200),
        ]);

        Time::posts()->delete('post123');

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/posts/post123"
                && $request->method() === 'DELETE'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_delete_post_returns_ok_status(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['status' => 'ok'], 200),
        ]);

        $response = Time::posts()->delete('post123');

        $this->assertSame('ok', $response['status']);
    }

    public function test_delete_post_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/post123" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->delete('post123');
    }

    public function test_get_by_ids_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/ids" => Http::response(
                [['id' => 'post1'], ['id' => 'post2']],
                200,
            ),
        ]);

        $response = Time::posts()->getByIds(new GetPostsByIdsData(['post1', 'post2']));

        $this->assertSame('post1', $response[0]['id']);
        $this->assertSame('post2', $response[1]['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/posts/ids"
                && $request->method() === 'POST'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ')
                && $request->data() === ['post1', 'post2'];
        });
    }

    public function test_get_by_ids_throws_time_api_exception_on_4xx(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/posts/ids" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::posts()->getByIds(new GetPostsByIdsData(['post1']));
    }

    public function test_get_posts_by_ids_data_rejects_empty_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GetPostsByIdsData([]);
    }
}
