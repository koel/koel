<?php

namespace App\Values;

class LastfmLoveTrackParameters
{
    private $trackName;
    private $artistName;

    private function __construct(string $trackName, string $artistName)
    {
        $this->trackName = $trackName;
        $this->artistName = $artistName;
    }

    public static function make(string $trackName, string $artistName): self
    {
        return new static($trackName, $artistName);
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
