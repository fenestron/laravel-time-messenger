<?php

namespace Fenestron\TimeMessenger\Resources;

use Fenestron\TimeMessenger\Data\DeleteReactionData;
use Fenestron\TimeMessenger\Data\SaveReactionData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Reactions are put on behalf of `$data->userId` or, when it is omitted, of the client's
 * default user ({@see TimeClient::$userId}).
 */
class ReactionsResource
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
    public function save(SaveReactionData $data): array
    {
        $body = $data->toBody($this->client->userId);

        return $this->client->send(
            fn (PendingRequest $request): Response => $request->post('/api/v4/reactions', $body),
        )->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function delete(DeleteReactionData $data): array
    {
        $path = $data->toPath($this->client->userId);

        return $this->client->send(
            fn (PendingRequest $request): Response => $request->delete($path),
        )->json();
    }
}
