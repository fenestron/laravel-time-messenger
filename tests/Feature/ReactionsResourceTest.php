<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Data\DeleteReactionData;
use Fenestron\TimeMessenger\Data\SaveReactionData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class ReactionsResourceTest extends TestCase
{
    private string $timeUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeUrl = config('time-messenger.base_url');

        Http::preventStrayRequests();
    }

    public function test_save_reaction_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/reactions" => Http::response([
                'user_id' => 'user123',
                'post_id' => 'post123',
                'emoji_name' => 'thumbsup',
                'create_at' => 1000,
            ], 201),
        ]);

        $data = new SaveReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
            userId: 'user123',
        );

        $response = Time::reactions()->save($data);

        $this->assertSame('post123', $response['post_id']);
        $this->assertSame('thumbsup', $response['emoji_name']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/reactions"
                && $request->method() === 'POST'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ')
                && $request['user_id'] === 'user123'
                && $request['post_id'] === 'post123'
                && $request['emoji_name'] === 'thumbsup';
        });
    }

    public function test_save_reaction_uses_user_id_from_config_when_not_provided(): void
    {
        $configUserId = config('time-messenger.user_id');

        Http::fake([
            "{$this->timeUrl}/api/v4/reactions" => Http::response([
                'user_id' => $configUserId,
                'post_id' => 'post456',
                'emoji_name' => 'heart',
                'create_at' => 2000,
            ], 201),
        ]);

        $data = new SaveReactionData(
            postId: 'post456',
            emojiName: 'heart',
        );

        Time::reactions()->save($data);

        Http::assertSent(function (Request $request) use ($configUserId) {
            return $request['user_id'] === $configUserId;
        });
    }

    public function test_save_reaction_uses_explicit_user_id_over_config(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/reactions" => Http::response([
                'user_id' => 'explicit_user',
                'post_id' => 'post789',
                'emoji_name' => '+1',
                'create_at' => 3000,
            ], 201),
        ]);

        $data = new SaveReactionData(
            postId: 'post789',
            emojiName: '+1',
            userId: 'explicit_user',
        );

        Time::reactions()->save($data);

        Http::assertSent(function (Request $request) {
            return $request['user_id'] === 'explicit_user';
        });
    }

    public function test_save_reaction_throws_time_api_exception_on_400(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/reactions" => Http::response(['message' => 'Bad Request'], 400),
        ]);

        $this->expectException(TimeApiException::class);

        Time::reactions()->save(new SaveReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
        ));
    }

    public function test_save_reaction_throws_time_api_exception_on_403(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/reactions" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        try {
            Time::reactions()->save(new SaveReactionData(
                postId: 'post123',
                emojiName: 'thumbsup',
            ));
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(403, $exception->status());
        }
    }

    public function test_delete_reaction_sends_correct_request(): void
    {
        $path = (new DeleteReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
            userId: 'user123',
        ))->toPath(config('time-messenger.user_id'));

        Http::fake([
            "{$this->timeUrl}{$path}" => Http::response(['status' => 'ok'], 200),
        ]);

        $response = Time::reactions()->delete(new DeleteReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
            userId: 'user123',
        ));

        $this->assertSame('ok', $response['status']);

        Http::assertSent(function (Request $request) use ($path) {
            return $request->url() === "{$this->timeUrl}{$path}"
                && $request->method() === 'DELETE'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_delete_reaction_uses_user_id_from_config_when_not_provided(): void
    {
        $path = (new DeleteReactionData(
            postId: 'post456',
            emojiName: 'heart',
        ))->toPath(config('time-messenger.user_id'));

        Http::fake([
            "{$this->timeUrl}{$path}" => Http::response(['status' => 'ok'], 200),
        ]);

        Time::reactions()->delete(new DeleteReactionData(
            postId: 'post456',
            emojiName: 'heart',
        ));

        Http::assertSent(function (Request $request) use ($path) {
            return $request->url() === "{$this->timeUrl}{$path}"
                && $request->method() === 'DELETE';
        });
    }

    public function test_delete_reaction_uses_explicit_user_id_over_config(): void
    {
        $path = (new DeleteReactionData(
            postId: 'post789',
            emojiName: '+1',
            userId: 'explicit_user',
        ))->toPath(config('time-messenger.user_id'));

        Http::fake([
            "{$this->timeUrl}{$path}" => Http::response(['status' => 'ok'], 200),
        ]);

        Time::reactions()->delete(new DeleteReactionData(
            postId: 'post789',
            emojiName: '+1',
            userId: 'explicit_user',
        ));

        Http::assertSent(function (Request $request) use ($path) {
            return $request->url() === "{$this->timeUrl}{$path}"
                && $request->method() === 'DELETE';
        });
    }

    public function test_delete_reaction_throws_time_api_exception_on_400(): void
    {
        $path = (new DeleteReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
        ))->toPath(config('time-messenger.user_id'));

        Http::fake([
            "{$this->timeUrl}{$path}" => Http::response(['message' => 'Bad Request'], 400),
        ]);

        $this->expectException(TimeApiException::class);

        Time::reactions()->delete(new DeleteReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
        ));
    }

    public function test_delete_reaction_throws_time_api_exception_on_401(): void
    {
        $path = (new DeleteReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
        ))->toPath(config('time-messenger.user_id'));

        Http::fake([
            "{$this->timeUrl}{$path}" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::reactions()->delete(new DeleteReactionData(
                postId: 'post123',
                emojiName: 'thumbsup',
            ));
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }

    public function test_delete_reaction_throws_time_api_exception_on_403(): void
    {
        $path = (new DeleteReactionData(
            postId: 'post123',
            emojiName: 'thumbsup',
        ))->toPath(config('time-messenger.user_id'));

        Http::fake([
            "{$this->timeUrl}{$path}" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        try {
            Time::reactions()->delete(new DeleteReactionData(
                postId: 'post123',
                emojiName: 'thumbsup',
            ));
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(403, $exception->status());
        }
    }
}
