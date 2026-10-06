<?php

namespace Fenestron\TimeMessenger\Resources;

use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class ChannelsResource
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
    public function get(string $channelId): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->get("/api/v4/channels/{$channelId}"),
        )->json();
    }

    /**
     * All channels from all teams the user is a member of.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function forUser(string $userId): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->get("/api/v4/users/{$userId}/channels"),
        )->json();
    }
}
