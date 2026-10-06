<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Data\UploadFileData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Fenestron\TimeMessenger\TimeClient;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class FilesResourceTest extends TestCase
{
    private string $timeUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timeUrl = config('time-messenger.base_url');

        Http::preventStrayRequests();
    }

    #[DefineEnvironment('useCustomFileDownloadTimeout')]
    public function test_file_download_uses_the_configured_timeout_instead_of_the_shared_one(): void
    {
        $timeouts = [];

        Http::fake(function (Request $request, array $options) use (&$timeouts): PromiseInterface {
            $timeouts[$request->url()] = $options['timeout'];

            return str_ends_with($request->url(), '/info')
                ? Http::response(['id' => 'file123'])
                : Http::response('bytes');
        });

        Time::files()->get('file123');
        Time::files()->info('file123');

        $this->assertSame(123, $this->app->make(TimeClient::class)->fileDownloadTimeout);
        $this->assertSame(123, $timeouts["{$this->timeUrl}/api/v4/files/file123"]);
        $this->assertSame(30, $timeouts["{$this->timeUrl}/api/v4/files/file123/info"]);
    }

    public function test_file_download_timeout_defaults_to_90_seconds(): void
    {
        $this->assertSame(90, config('time-messenger.file_download_timeout'));
        $this->assertSame(90, $this->app->make(TimeClient::class)->fileDownloadTimeout);
    }

    public function test_upload_sends_correct_multipart_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [['id' => 'file123', 'name' => 'file.txt']],
                'client_ids'  => [],
            ], 201),
        ]);

        $data = new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        );

        $response = Time::files()->upload($data);

        $this->assertSame('file123', $response['file_infos'][0]['id']);

        Http::assertSent(function (Request $request) {
            return $request->url() === "{$this->timeUrl}/api/v4/files"
                && $request->method() === 'POST'
                && str_starts_with($request->header('Authorization')[0], 'Bearer ')
                && str_contains($request->header('Content-Type')[0], 'multipart/form-data');
        });
    }

    public function test_upload_includes_channel_id(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [['id' => 'file123']],
                'client_ids'  => [],
            ], 201),
        ]);

        $data = new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        );

        Time::files()->upload($data);

        Http::assertSent(function (Request $request) {
            return str_contains($request->body(), 'name="channel_id"')
                && str_contains($request->body(), 'chan123');
        });
    }

    public function test_upload_includes_client_id_when_provided(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [['id' => 'file123']],
                'client_ids'  => ['client-abc'],
            ], 201),
        ]);

        $data = new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
            clientId: 'client-abc',
        );

        Time::files()->upload($data);

        Http::assertSent(function (Request $request) {
            return str_contains($request->body(), 'name="client_ids"')
                && str_contains($request->body(), 'client-abc');
        });
    }

    public function test_upload_omits_client_id_when_not_provided(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [['id' => 'file123']],
                'client_ids'  => [],
            ], 201),
        ]);

        $data = new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        );

        Time::files()->upload($data);

        Http::assertSent(function (Request $request) {
            return !str_contains($request->body(), 'name="client_ids"');
        });
    }

    public function test_upload_returns_file_infos_and_client_ids(): void
    {
        $fileInfo = ['id' => 'file123', 'name' => 'file.txt', 'size' => 512];

        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [$fileInfo],
                'client_ids'  => ['client-abc'],
            ], 201),
        ]);

        $response = Time::files()->upload(new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        ));

        $this->assertSame([$fileInfo], $response['file_infos']);
        $this->assertSame(['client-abc'], $response['client_ids']);
    }

    public function test_upload_uses_bearer_token_from_config(): void
    {
        $token = config('time-messenger.token');

        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [['id' => 'file123']],
                'client_ids'  => [],
            ], 201),
        ]);

        Time::files()->upload(new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        ));

        Http::assertSent(function (Request $request) use ($token) {
            return $request->header('Authorization')[0] === "Bearer {$token}";
        });
    }

    public function test_upload_throws_time_api_exception_on_400(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response(['message' => 'Bad Request'], 400),
        ]);

        $this->expectException(TimeApiException::class);

        Time::files()->upload(new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        ));
    }

    public function test_upload_throws_time_api_exception_on_401(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::files()->upload(new UploadFileData(
                contents: 'binary-data',
                filename: 'file.txt',
                channelId: 'chan123',
            ));
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }

    public function test_upload_throws_time_api_exception_on_403(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::files()->upload(new UploadFileData(
            contents: 'binary-data',
            filename: 'file.txt',
            channelId: 'chan123',
        ));
    }

    public function test_upload_throws_time_api_exception_on_413(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response(['message' => 'Content Too Large'], 413),
        ]);

        try {
            Time::files()->upload(new UploadFileData(
                contents: 'binary-data',
                filename: 'file.txt',
                channelId: 'chan123',
            ));
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(413, $exception->status());
        }
    }

    public function test_upload_sends_multiple_files_in_single_request(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => [
                    ['id' => 'file1', 'name' => 'file1.txt'],
                    ['id' => 'file2', 'name' => 'file2.txt'],
                ],
                'client_ids' => [],
            ], 201),
        ]);

        Time::files()->upload(
            new UploadFileData(contents: 'data-1', filename: 'file1.txt', channelId: 'chan123'),
            new UploadFileData(contents: 'data-2', filename: 'file2.txt', channelId: 'chan123'),
        );

        Http::assertSentCount(1);

        Http::assertSent(function (Request $request) {
            if ($request->url() !== "{$this->timeUrl}/api/v4/files" || $request->method() !== 'POST') {
                return false;
            }

            $body = $request->body();

            return substr_count($body, 'name="files"') === 2
                && str_contains($body, 'file1.txt')
                && str_contains($body, 'file2.txt')
                && str_contains($body, 'name="channel_id"')
                && str_contains($body, 'chan123');
        });
    }

    public function test_upload_returns_all_file_infos_for_multiple_files(): void
    {
        $fileInfos = [
            ['id' => 'file1', 'name' => 'file1.txt', 'size' => 100],
            ['id' => 'file2', 'name' => 'file2.txt', 'size' => 200],
        ];

        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response([
                'file_infos' => $fileInfos,
                'client_ids'  => [],
            ], 201),
        ]);

        $response = Time::files()->upload(
            new UploadFileData(contents: 'data-1', filename: 'file1.txt', channelId: 'chan123'),
            new UploadFileData(contents: 'data-2', filename: 'file2.txt', channelId: 'chan123'),
        );

        $this->assertSame($fileInfos, $response['file_infos']);
        $this->assertSame('file1', $response['file_infos'][0]['id']);
        $this->assertSame('file2', $response['file_infos'][1]['id']);
    }

    public function test_upload_throws_on_error_with_multiple_files(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files" => Http::response(['message' => 'Bad Request'], 400),
        ]);

        $this->expectException(TimeApiException::class);

        Time::files()->upload(
            new UploadFileData(contents: 'data-1', filename: 'file1.txt', channelId: 'chan123'),
            new UploadFileData(contents: 'data-2', filename: 'file2.txt', channelId: 'chan123'),
        );
    }

    public function test_get_sends_correct_request(): void
    {
        $token = config('time-messenger.token');

        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123" => Http::response('binary-content', 200),
        ]);

        Time::files()->get('file123');

        Http::assertSent(function (Request $request) use ($token) {
            return $request->url() === "{$this->timeUrl}/api/v4/files/file123"
                && $request->method() === 'GET'
                && $request->header('Authorization')[0] === "Bearer {$token}"
                && in_array('application/octet-stream', $request->header('Accept'));
        });
    }

    public function test_get_returns_file_contents(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123" => Http::response('binary-content', 200),
        ]);

        $result = Time::files()->get('file123');

        $this->assertSame('binary-content', $result);
    }

    public function test_get_throws_time_api_exception_on_401(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::files()->get('file123');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }

    public function test_get_throws_time_api_exception_on_404(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files/nonexistent" => Http::response(['message' => 'Not Found'], 404),
        ]);

        try {
            Time::files()->get('nonexistent');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(404, $exception->status());
        }
    }

    public function test_info_sends_correct_request(): void
    {
        $token = config('time-messenger.token');

        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123/info" => Http::response(['id' => 'file123'], 200),
        ]);

        Time::files()->info('file123');

        Http::assertSent(function (Request $request) use ($token) {
            return $request->url() === "{$this->timeUrl}/api/v4/files/file123/info"
                && $request->method() === 'GET'
                && $request->header('Authorization')[0] === "Bearer {$token}";
        });
    }

    public function test_info_returns_file_metadata(): void
    {
        $fileInfo = [
            'id'         => 'file123',
            'user_id'    => 'user456',
            'channel_id' => 'chan789',
            'name'       => 'document.pdf',
            'size'       => 1024,
            'mime_type'  => 'application/pdf',
        ];

        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123/info" => Http::response($fileInfo, 200),
        ]);

        $result = Time::files()->info('file123');

        $this->assertSame($fileInfo, $result);
    }

    public function test_info_throws_time_api_exception_on_401(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123/info" => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            Time::files()->info('file123');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(401, $exception->status());
        }
    }

    public function test_info_throws_time_api_exception_on_403(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files/file123/info" => Http::response(['message' => 'Forbidden'], 403),
        ]);

        $this->expectException(TimeApiException::class);

        Time::files()->info('file123');
    }

    public function test_info_throws_time_api_exception_on_404(): void
    {
        Http::fake([
            "{$this->timeUrl}/api/v4/files/nonexistent/info" => Http::response(['message' => 'Not Found'], 404),
        ]);

        try {
            Time::files()->info('nonexistent');
            $this->fail('Expected TimeApiException');
        } catch (TimeApiException $exception) {
            $this->assertSame(404, $exception->status());
        }
    }

    public function test_download_connection_failure_becomes_time_connection_exception(): void
    {
        $original = new ConnectionException('cURL error 28: Operation timed out');

        Http::fake(["{$this->timeUrl}/api/v4/files/file123" => fn (): never => throw $original]);

        try {
            Time::files()->get('file123');
            $this->fail('Expected TimeConnectionException');
        } catch (TimeConnectionException $exception) {
            $this->assertSame($original, $exception->getPrevious());
        }
    }

    public function test_upload_connection_failure_becomes_time_connection_exception(): void
    {
        $original = new ConnectionException('cURL error 35: SSL connect error');

        Http::fake(["{$this->timeUrl}/api/v4/files" => fn (): never => throw $original]);

        try {
            Time::files()->upload(new UploadFileData(contents: 'binary-data', filename: 'file.txt', channelId: 'chan123'));
            $this->fail('Expected TimeConnectionException');
        } catch (TimeConnectionException $exception) {
            $this->assertSame($original, $exception->getPrevious());
        }
    }

    /**
     * @param  Application  $app
     */
    protected function useCustomFileDownloadTimeout($app): void
    {
        $app['config']->set('time-messenger.file_download_timeout', 123);
    }
}
