<?php

namespace App\Values;

use App\Models\Song as Playable;
use Illuminate\Support\Collection;

final class QueueState
{
    private function __construct(
        public Collection $playables,
        public ?Playable $currentPlayable,
        public ?int $playbackPosition
    ) {
    }

    public static function make(Collection $songs, ?Playable $currentPlayable = null, ?int $playbackPosition = 0): self
    {
        return new self($songs, $currentPlayable, $playbackPosition);
    }
}
