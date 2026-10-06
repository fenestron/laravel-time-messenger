<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Exceptions;

use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use PHPUnit\Framework\TestCase;

class TimeApiExceptionTest extends TestCase
{
    public function test_exposes_status_body_and_json(): void
    {
        $exception = $this->failWith(Factory::response(['message' => 'Forbidden', 'detailed_error' => ['id' => 'api.post']], 403));

        $this->assertSame(403, $exception->status());
        $this->assertSame(403, $exception->getCode());
        $this->assertSame('{"message":"Forbidden","detailed_error":{"id":"api.post"}}', $exception->body());
        $this->assertSame('Forbidden', $exception->json('message'));
        $this->assertSame('api.post', $exception->json('detailed_error.id'));
        $this->assertSame(['message' => 'Forbidden', 'detailed_error' => ['id' => 'api.post']], $exception->json());
        $this->assertNull($exception->json('missing'));
        $this->assertSame('Time API returned 403: {"message":"Forbidden","detailed_error":{"id":"api.post"}}', $exception->getMessage());
        $this->assertInstanceOf(RequestException::class, $exception->getPrevious());
    }

    public function test_json_is_null_for_a_non_json_body(): void
    {
        $exception = $this->failWith(Factory::response('Bad Gateway', 502));

        $this->assertSame('Bad Gateway', $exception->body());
        $this->assertNull($exception->json());
        $this->assertNull($exception->json('message'));
    }

    public function test_can_be_constructed_without_laravel_types(): void
    {
        $exception = new TimeApiException('Time API returned 404: {}', 404, '{}');

        $this->assertSame(404, $exception->status());
        $this->assertSame([], $exception->json());
    }

    private function failWith(mixed $response): TimeApiException
    {
        $http = new Factory;
        $http->fake(fn (): mixed => $response);

        try {
            (new TimeClient('https://time.test', 'secret', http: $http))->users()->get('me');
        } catch (TimeApiException $exception) {
            return $exception;
        }

        $this->fail('Expected TimeApiException');
    }
}
