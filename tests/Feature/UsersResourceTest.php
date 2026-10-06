<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class UsersResourceTest extends TestCase
{
    private string $timeUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeUrl = config('time-messenger.base_url');

        Http::preventStrayRequests();
    }

    public function test_get_user_sends_correct_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/users/user123" => Http::response(['id' => 'user123', 'username' => 'ivan'], 200),
        ]);

        $response = Time::users()->get('user123');

        $this->assertSame('user123', $response['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/users/user123"
                && $request->method() === 'GET'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ');
        });
    }

    public function test_get_current_user_via_me(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/users/me" => Http::response(['id' => 'self123', 'username' => 'bot'], 200),
        ]);

        $response = Time::users()->get('me');

        $this->assertSame('self123', $response['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/users/me"
                && $request->method() === 'GET';
        });
    }

    public function test_get_user_throws_time_api_exception_on_404(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/users/missing" => Http::response(['message' => 'Not Found'], 404),
        ]);

        $this->expectException(TimeApiException::class);

        Time::users()->get('missing');
    }

    public function test_get_user_exception_has_correct_status(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/users/user123" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::users()->get('user123');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }
}
