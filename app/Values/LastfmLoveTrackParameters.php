<?php

namespace App\Values;

final class LastfmLoveTrackParameters
{
    private string $trackName;
    private string $artistName;

    private function __construct(string $trackName, string $artistName)
    {
        $this->trackName = $trackName;
        $this->artistName = $artistName;
    }

    public static function make(string $trackName, string $artistName): self
    {
        return new self($trackName, $artistName);
    }

    public function getTrackName(): string
    {
        return $this->trackName;
    }

    public function getArtistName(): string
    {
        return $this->artistName;
    }
}
