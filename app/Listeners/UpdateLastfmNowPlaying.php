<?php

namespace App\Listeners;

use App\Events\PlaybackStarted;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLastfmNowPlaying implements ShouldQueue
{
    public function __construct(private readonly LastfmService $lastfm)
    {
    }

    public function handle(PlaybackStarted $event): void
    {
        if (
            !LastfmService::enabled()
            || !$event->user->preferences->lastFmSessionKey
            || $event->song->artist->is_unknown
        ) {
            return;
        }

        $this->lastfm->updateNowPlaying($event->song, $event->user);
    }
}
