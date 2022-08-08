<?php

namespace App\Listeners;

use App\Events\SongStartedPlaying;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLastfmNowPlaying implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(SongStartedPlaying $event): void
    {
        if (!LastfmService::enabled() || !$event->user->lastfm_session_key || $event->song->artist->is_unknown) {
            return;
        }

        $this->lastfm->updateNowPlaying($event->song, $event->user);
    }
}
