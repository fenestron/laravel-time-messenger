<?php

namespace Fenestron\TimeMessenger\Exceptions;

use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

/**
 * The Time API responded with a 4xx or 5xx status.
 */
class TimeApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status,
        private readonly string $body,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * The decoded JSON body, or a single value from it by a dot-notation key.
     * Returns `null` when the body is not valid JSON or the key is missing.
     */
    public function json(?string $key = null): mixed
    {
        $decoded = json_decode($this->body, true);

        if ($key === null) {
            return $decoded;
        }

        return data_get($decoded, $key);
    }

    /**
     * @internal
     */
    public static function fromRequestException(RequestException $exception): self
    {
        $status = $exception->response->status();
        $body   = $exception->response->body();

        return new self(
            message: "Time API returned {$status}: {$body}",
            status: $status,
            body: $body,
            previous: $exception,
        );
    }
}
