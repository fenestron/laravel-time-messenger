<?php

namespace Fenestron\TimeMessenger;

use Fenestron\TimeMessenger\Exceptions\TimeApiException;
use Fenestron\TimeMessenger\Exceptions\TimeConnectionException;
use Illuminate\Contracts\Debug\ExceptionHandler;

/**
 * Attachments of a Time post: from a websocket `posted` frame or from a REST response.
 *
 * Split into two steps on purpose. {@see self::refsFromPost()} makes no requests, so it is safe
 * inside a websocket listener where going to the network would stall every other event: the
 * `posted` frame carries the whole `metadata.files` (`id`, `name`, `size`, `mime_type`), which is
 * enough. {@see self::resolveMissing()} does make requests and is only needed for the fallback
 * on a frame with bare `file_ids`. Get an instance with {@see TimeClient::attachments()}.
 */
class TimePostAttachments
{
    public function __construct(
        private readonly TimeClient $client,
    ) {
    }

    /**
     * Attachment references of a post, without a single request to Time.
     *
     * An empty `name` and a zero `size` come only from the `file_ids` fallback, where the post
     * carries no metadata at all. {@see self::resolveMissing()} fills them in.
     *
     * @param  array<string, mixed>  $post
     * @return list<array{id: string, name: string, size: int}>
     */
    public static function refsFromPost(array $post): array
    {
        $files = $post['metadata']['files'] ?? null;

        if (is_array($files) && $files !== []) {
            $refs = [];

            foreach ($files as $file) {
                if (!is_array($file)) {
                    continue;
                }

                $id = (string) ($file['id'] ?? '');

                if ($id === '') {
                    continue;
                }

                $refs[] = [
                    'id'   => $id,
                    'name' => (string) ($file['name'] ?? ''),
                    'size' => (int) ($file['size'] ?? 0),
                ];
            }

            return $refs;
        }

        $fileIds = $post['file_ids'] ?? null;

        if (!is_array($fileIds)) {
            return [];
        }

        $refs = [];

        foreach ($fileIds as $fileId) {
            $id = (string) $fileId;

            if ($id === '') {
                continue;
            }

            $refs[] = ['id' => $id, 'name' => '', 'size' => 0];
        }

        return $refs;
    }

    /**
     * Fills in an empty name and, with `$sizeRequired`, a zero size: one `files()->info()` per
     * file, the response carries both.
     *
     * The size is requested only on demand: a caller that does not filter files by size before
     * downloading them would pay an extra request per attachment for nothing.
     *
     * A Time API error here is not fatal: the name falls back to the file id, the size stays
     * zero, and the caller decides what to do with the file. One attachment with an unknown name
     * must not cost the whole message. The error is reported through Laravel's `report()` inside a
     * Laravel application. Connection failures are not swallowed.
     *
     * @param  list<array{id: string, name: string, size: int}>  $refs
     * @return list<array{id: string, name: string, size: int}>
     *
     * @throws TimeConnectionException
     */
    public function resolveMissing(array $refs, bool $sizeRequired = false): array
    {
        foreach ($refs as $index => $ref) {
            if ($ref['name'] !== '' && (!$sizeRequired || $ref['size'] > 0)) {
                continue;
            }

            try {
                $info = $this->client->files()->info($ref['id']);
            } catch (TimeApiException $exception) {
                self::report($exception);

                $refs[$index]['name'] = $ref['name'] !== '' ? $ref['name'] : $ref['id'];

                continue;
            }

            $name = (string) ($info['name'] ?? '');
            $size = (int) ($info['size'] ?? 0);

            $refs[$index]['name'] = $ref['name'] !== '' ? $ref['name'] : ($name !== '' ? $name : $ref['id']);
            $refs[$index]['size'] = $ref['size'] > 0 ? $ref['size'] : $size;
        }

        return $refs;
    }

    /**
     * Hands the error to Laravel's exception handler when the client runs inside a Laravel
     * application, and does nothing otherwise.
     */
    private static function report(TimeApiException $exception): void
    {
        if (function_exists('report') && function_exists('app') && app()->bound(ExceptionHandler::class)) {
            report($exception);
        }
    }
}
