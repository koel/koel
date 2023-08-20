<?php

namespace App\DTO;

final class LastfmLoveTrackParameters
{
    private function __construct(public string $trackName, public string $artistName)
    {
    }

    public static function make(string $trackName, string $artistName): self
    {
        return new self($trackName, $artistName);
    }
}
