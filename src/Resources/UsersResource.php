<?php

namespace Fenestron\TimeMessenger\Resources;

use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class UsersResource
{
    public function __construct(
        private readonly TimeClient $client,
    ) {
    }

    /**
     * @param  string  $userId  User id, or `me` for the owner of the token.
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function get(string $userId): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->get("/api/v4/users/{$userId}"),
        )->json();
    }
}
