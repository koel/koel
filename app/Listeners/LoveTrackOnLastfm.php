<?php

namespace App\Listeners;

use App\Events\SongLikeToggled;
use App\Services\LastfmService;

class LoveTrackOnLastfm
{
    /**
     * The Last.fm service instance, which is DI'ed into our listener.
     *
     * @var LastfmService
     */
    protected $lastfm;

    /**
     * Create the event listener.
     *
     * @param LastfmService $lastfm
     */
    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    /**
     * Handle the event.
     *
     * @param SongLikeToggled $event
     */
    public function handle(SongLikeToggled $event)
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
