<?php

namespace App\Listeners;

use App\Events\SongStartedPlaying;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLastfmNowPlaying implements ShouldQueue
{
    private LastfmService $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongStartedPlaying $event): void
    {
        if (!$this->lastfm->enabled() || !$event->user->lastfm_session_key || $event->song->artist->is_unknown) {
            return;
        }

        $this->lastfm->updateNowPlaying(
            $event->song->artist->name,
            $event->song->title,
            $event->song->album->is_unknown ? '' : $event->song->album->name,
            $event->song->length,
            $event->user->lastfm_session_key
        );
    }
}
