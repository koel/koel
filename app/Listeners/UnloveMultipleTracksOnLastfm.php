<?php

namespace App\Listeners;

use App\Events\MultipleSongsUnliked;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class UnloveMultipleTracksOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(MultipleSongsUnliked $event): void
    {
        $songs = $event->songs->filter(static fn ($song) => !$song->isEpisode() && !$song->artist->is_unknown);

        if ($songs->isEmpty() || !LastfmService::enabled() || !$event->user->preferences->lastFmSessionKey) {
            return;
        }

        $this->lastfm->batchToggleLoveTracks($event->songs, $event->user, false);
    }
}
