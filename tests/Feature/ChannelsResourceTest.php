<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class ChannelsResourceTest extends TestCase
{
    private string $timeUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeUrl = config('time-messenger.base_url');

        Http::preventStrayRequests();
    }

    public function test_get_channel_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/channels/chan123" => Http::response([
                'id'           => 'chan123',
                'name'         => 'general',
                'type'         => 'O',
                'display_name' => 'General',
            ], 200),
        ]);

        $response = Time::channels()->get('chan123');

        $this->assertSame('chan123', $response['id']);
        $this->assertSame('General', $response['display_name']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/channels/chan123"
                && $request->method() === 'GET'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_get_channel_throws_time_api_exception_on_404(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/channels/missing" => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->expectException(TimeApiException::class);

        Time::channels()->get('missing');
    }

    public function test_get_channel_exception_has_correct_status(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/channels/chan123" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::channels()->get('chan123');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }

    public function test_get_user_channels_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/users/user123/channels" => Http::response([
                [
                    'id'           => 'chan123',
                    'name'         => 'general',
                    'type'         => 'O',
                    'display_name' => 'General',
                ],
                [
                    'id'           => 'chan456',
                    'name'         => 'random',
                    'type'         => 'P',
                    'display_name' => 'Random',
                ],
            ], 200),
        ]);

        $response = Time::channels()->forUser('user123');

        $this->assertCount(2, $response);
        $this->assertSame('chan123', $response[0]['id']);
        $this->assertSame('Random', $response[1]['display_name']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/users/user123/channels"
                && $request->method() === 'GET'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_get_user_channels_throws_time_api_exception_on_403(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/users/user123/channels" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        try {
            Time::channels()->forUser('user123');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(403, $exception->status());
        }
    }
}
