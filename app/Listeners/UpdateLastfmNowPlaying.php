<?php

namespace App\Listeners;

use App\Events\SongStartedPlaying;
use App\Models\Album;
use App\Services\LastfmService;

class UpdateLastfmNowPlaying
{
    protected $lastfm;

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

        $this->lastfm->updateNowPlaying(
            $event->song->artist->name,
            $event->song->title,
            $event->song->album->name === Album::UNKNOWN_NAME ? null : $event->song->album->name,
            $event->song->length,
            $sessionKey
        );
    }
}
