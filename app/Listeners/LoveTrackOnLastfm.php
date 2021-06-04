<?php

namespace App\Listeners;

use App\Events\SongLikeToggled;
use App\Services\LastfmService;
use App\Values\LastfmLoveTrackParameters;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoveTrackOnLastfm implements ShouldQueue
{
    private $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongLikeToggled $event): void
    {
        if (
            !$this->lastfm->enabled() ||
            !$event->user->lastfm_session_key ||
            $event->interaction->song->artist->is_unknown
        ) {
            return;
        }

        $this->lastfm->toggleLoveTrack(
            LastfmLoveTrackParameters::make(
                $event->interaction->song->title,
                $event->interaction->song->artist->name,
            ),
            $event->user->lastfm_session_key,
            $event->interaction->liked
        );
    }
}
