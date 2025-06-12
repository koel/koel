<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class FailedToParsePodcastFeedException extends RuntimeException
{
    public static function create(string $url, Throwable $previous): self
    {
        return new self("Failed to parse the podcast feed at $url.", $previous->getCode(), $previous);
    }
}
