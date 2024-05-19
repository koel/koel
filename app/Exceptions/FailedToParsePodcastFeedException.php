<?php

namespace App\Exceptions;

use Exception;
use Throwable;

final class FailedToParsePodcastFeedException extends Exception
{
    private function __construct(string $url, Throwable $previous)
    {
        parent::__construct("Failed to parse the podcast feed at $url.", (int) $previous->getCode(), $previous);
    }

    public static function create(string $url, Throwable $previous): self
    {
        return new self($url, $previous);
    }
}
