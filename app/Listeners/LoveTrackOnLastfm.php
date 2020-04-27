<?php

namespace App\Listeners;

use App\Events\SongLikeToggled;
use App\Jobs\LoveTrackOnLastfmJob;
use App\Services\LastfmService;

class LoveTrackOnLastfm
{
    private $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongLikeToggled $event): void
    {
        if (!$this->lastfm->enabled() ||
            !($sessionKey = $event->user->lastfm_session_key) ||
            $event->interaction->song->artist->is_unknown
        ) {
            return;
        }

        LoveTrackOnLastfmJob::dispatch($event->user, $event->interaction);
    }
}
