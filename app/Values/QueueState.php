<?php

namespace App\Values;

use App\Models\Song;
use Illuminate\Support\Collection;

final class QueueState
{
    private function __construct(public Collection $songs, public ?Song $currentSong, public ?int $playbackPosition)
    {
    }

    public static function make(Collection $songs, ?Song $currentSong = null, ?int $playbackPosition = 0): self
    {
        return new self($songs, $currentSong, $playbackPosition);
    }
}
