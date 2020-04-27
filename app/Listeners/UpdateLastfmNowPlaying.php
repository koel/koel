<?php

namespace App\Listeners;

use App\Events\SongStartedPlaying;
use App\Jobs\UpdateLastfmNowPlayingJob;
use App\Services\LastfmService;

class UpdateLastfmNowPlaying
{
    private $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongStartedPlaying $event): void
    {
        if (!$this->lastfm->enabled() ||
            !($sessionKey = $event->user->lastfm_session_key) ||
            $event->song->artist->is_unknown
        ) {
            return;
        }

        UpdateLastfmNowPlayingJob::dispatch($event->user, $event->song);
    }
}
