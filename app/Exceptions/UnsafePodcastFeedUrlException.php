<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsafePodcastFeedUrlException extends RuntimeException
{
    public static function create(string $url): self
    {
        return new self("The podcast feed URL $url is not safe to fetch.");
    }
}
