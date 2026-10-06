<?php

namespace Fenestron\TimeMessenger\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

/**
 * The Time API could not be reached: DNS, TLS, connect or read timeout.
 *
 * The original exception is available through {@see self::getPrevious()}.
 */
class TimeConnectionException extends RuntimeException
{
    /**
     * @internal
     */
    public static function fromConnectionException(ConnectionException $exception): self
    {
        return new self(
            "Time API connection failed: {$exception->getMessage()}",
            (int) $exception->getCode(),
            $exception,
        );
    }
}
