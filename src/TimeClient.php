<?php

namespace Fenestron\TimeMessenger;

use Closure;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\Resources\ChannelsResource;
use Fenestron\TimeMessenger\Resources\FilesResource;
use Fenestron\TimeMessenger\Resources\PostsResource;
use Fenestron\TimeMessenger\Resources\ReactionsResource;
use Fenestron\TimeMessenger\Resources\UsersResource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

class TimeClient
{
    private readonly Factory $http;

    /**
     * @param  string  $baseUrl  Base URL of the Time installation, e.g. `https://time.example.com`.
     * @param  string  $token  Personal access token or bot token.
     * @param  string|null  $userId  Default user for reactions when the data object does not specify one.
     * @param  int  $fileDownloadTimeout  Timeout in seconds for {@see FilesResource::get()}.
     * @param  Factory|null  $http  @internal Lets the Laravel integration share the application's
     *                              HTTP factory so that `Http::fake()` intercepts the requests.
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        public readonly ?string $userId = null,
        public readonly int $fileDownloadTimeout = 90,
        ?Factory $http = null,
    ) {
        $this->http = $http ?? new Factory;
    }

    public function posts(): PostsResource
    {
        return new PostsResource($this);
    }

    public function reactions(): ReactionsResource
    {
        return new ReactionsResource($this);
    }

    public function users(): UsersResource
    {
        return new UsersResource($this);
    }

    public function files(): FilesResource
    {
        return new FilesResource($this);
    }

    public function channels(): ChannelsResource
    {
        return new ChannelsResource($this);
    }

    public function attachments(): TimePostAttachments
    {
        return new TimePostAttachments($this);
    }

    /**
     * Sends a request to the Time API. Every resource goes through this method, so HTTP errors
     * and connection failures are always translated into the package's own exceptions.
     *
     * @internal
     *
     * @param  Closure(PendingRequest): Response  $callback
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function send(Closure $callback, bool $multipart = false): Response
    {
        $request = $multipart ? $this->multipartRequest() : $this->request();

        try {
            return $callback($request)->throw();
        } catch (RequestException $exception) {
            throw TimeApiException::fromRequestException($exception);
        } catch (ConnectionException $exception) {
            throw TimeConnectionException::fromConnectionException($exception);
        }
    }

    private function request(): PendingRequest
    {
        return $this->http->baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10);
    }

    private function multipartRequest(): PendingRequest
    {
        return $this->http->baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(60)
            ->connectTimeout(10);
    }
}
