<?php

namespace Fenestron\TimeMessenger\Resources;

use Fenestron\TimeMessenger\Data\CreateEphemeralPostData;
use Fenestron\TimeMessenger\Data\CreatePostData;
use Fenestron\TimeMessenger\Data\GetChannelPostsData;
use Fenestron\TimeMessenger\Data\GetPostsByIdsData;
use Fenestron\TimeMessenger\Data\UpdatePostData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class PostsResource
{
    public function __construct(
        private readonly TimeClient $client,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function create(CreatePostData $data): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request
                ->withQueryParameters($data->toQuery())
                ->post('/api/v4/posts', $data->toBody()),
        )->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function createEphemeral(CreateEphemeralPostData $data): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->post('/api/v4/posts/ephemeral', $data->toBody()),
        )->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function get(string $postId): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->get("/api/v4/posts/{$postId}"),
        )->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function forChannel(string $channelId, ?GetChannelPostsData $data = null): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request
                ->withQueryParameters($data?->toQuery() ?? [])
                ->get("/api/v4/channels/{$channelId}/posts"),
        )->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function update(UpdatePostData $data): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->put("/api/v4/posts/{$data->id}", $data->toBody()),
        )->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function delete(string $postId): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->delete("/api/v4/posts/{$postId}"),
        )->json();
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function getByIds(GetPostsByIdsData $data): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->post('/api/v4/posts/ids', $data->toBody()),
        )->json();
    }
}
