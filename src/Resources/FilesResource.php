<?php

namespace Fenestron\TimeMessenger\Resources;

use Fenestron\TimeMessenger\Data\UploadFileData;
use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class FilesResource
{
    public function __construct(
        private readonly TimeClient $client,
    ) {
    }

    /**
     * File contents.
     *
     * Uses {@see TimeClient::$fileDownloadTimeout} instead of the 30-second limit of JSON calls:
     * downloading megabytes and a JSON call are different operations, and the shared limit
     * dropped attachments of several megabytes on a slow channel. See the README for the
     * reasoning behind the default value.
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function get(string $fileId): string
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request
                ->accept('application/octet-stream')
                ->timeout($this->client->fileDownloadTimeout)
                ->get("/api/v4/files/{$fileId}"),
        )->body();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function info(string $fileId): array
    {
        return $this->client->send(
            fn (PendingRequest $request): Response => $request->get("/api/v4/files/{$fileId}/info"),
        )->json();
    }

    /**
     * @param  non-empty-list<UploadFileData>  $files
     * @return array<string, mixed>
     *
     * @throws TimeApiException
     * @throws TimeConnectionException
     */
    public function upload(UploadFileData ...$files): array
    {
        $body = array_filter([
            'channel_id' => $files[0]->channelId,
            'client_ids' => count($files) === 1 ? $files[0]->clientId : null,
        ], fn (mixed $value): bool => $value !== null);

        return $this->client->send(
            function (PendingRequest $request) use ($files, $body): Response {
                foreach ($files as $file) {
                    $request = $request->attach('files', $file->contents, $file->filename);
                }

                return $request->post('/api/v4/files', $body);
            },
            multipart: true,
        )->json();
    }
}
