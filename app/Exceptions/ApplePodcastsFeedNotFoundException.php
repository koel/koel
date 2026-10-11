<?php

namespace App\Exceptions;

use RuntimeException;

final class ApplePodcastsFeedNotFoundException extends RuntimeException
{
    public static function create(string $url): self
    {
        return new self("Couldn't find a public feed for the Apple Podcasts link $url.");
    }
}
