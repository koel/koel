<?php

namespace App\Listeners;

use App\Events\SongStartedPlaying;
use App\Models\Album;
use App\Services\Lastfm;

class UpdateLastfmNowPlaying
{
    /**
     * The Last.fm service instance.
     *
     * @var Lastfm
     */
    protected $lastfm;

    /**
     * Create the event listener.
     *
     * @param Lastfm $lastfm
     */
    public function __construct(Lastfm $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    /**
     * Handle the event.
     *
     * @param SongStartedPlaying $event
     */
    public function handle(SongStartedPlaying $event)
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
