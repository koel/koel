<?php

namespace App\Listeners;

use App\Events\MultipleSongsLiked;
use App\Models\Song;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class LoveMultipleTracksOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(MultipleSongsLiked $event): void
    {
        $songs = $event->songs->filter(static fn (Song $song) => !$song->isEpisode() && !$song->artist->is_unknown);

        if ($songs->isEmpty() || !LastfmService::enabled() || !$event->user->preferences->lastFmSessionKey) {
            return;
        }

        $this->lastfm->batchToggleLoveTracks($songs, $event->user, true);
    }
}
