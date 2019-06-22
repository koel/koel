<?php

namespace App\Listeners;

use App\Events\SongLikeToggled;
use App\Services\LastfmService;

class LoveTrackOnLastfm
{
    protected $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongLikeToggled $event): void
    {
        if (!$this->lastfm->enabled() ||
            !($sessionKey = $event->user->lastfm_session_key) ||
            $event->interaction->song->album->artist->is_unknown
        ) {
            return;
        }

        $this->lastfm->toggleLoveTrack(
            $event->interaction->song->title,
            $event->interaction->song->album->artist->name,
            $sessionKey,
            $event->interaction->liked
        );
    }
}
