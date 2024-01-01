<?php

namespace App\Values;

use App\Models\Song;
use Illuminate\Support\Collection;

class QueueState
{
    private function __construct(public Collection $songs, public ?Song $currentSong, public ?int $playbackPosition)
    {
    }

    public static function create(Collection $songs, ?Song $currentSong = null, ?int $playbackPosition = 0): static
    {
        return new static($songs, $currentSong, $playbackPosition);
    }
}
