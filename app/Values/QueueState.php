<?php

namespace App\Values;

use App\Models\Song as Playable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class QueueState
{
    /**
     * @param Collection<int, Playable> $playables
     */
    private function __construct(
        public Collection $playables,
        public ?Playable $currentPlayable,
        public ?int $playbackPosition,
        public ?string $changedBy,
        public ?Carbon $changedAt,
    ) {}

    /**
     * @param Collection<int, Playable> $songs
     */
    public static function make(
        Collection $songs,
        ?Playable $currentPlayable = null,
        ?int $playbackPosition = 0,
        ?string $changedBy = null,
        ?Carbon $changedAt = null,
    ): self {
        return new self($songs, $currentPlayable, $playbackPosition, $changedBy, $changedAt);
    }
}
