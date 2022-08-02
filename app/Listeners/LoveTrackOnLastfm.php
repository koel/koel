<?php

namespace App\Listeners;

use App\Events\SongLikeToggled;
use App\Services\LastfmService;
use App\Values\LastfmLoveTrackParameters;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoveTrackOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(SongLikeToggled $event): void
    {
        if (
            !$this->lastfm->enabled() ||
            !$event->interaction->user->lastfm_session_key ||
            $event->interaction->song->artist->is_unknown
        ) {
            return;
        }

        $this->lastfm->toggleLoveTrack(
            LastfmLoveTrackParameters::make($event->interaction->song->title, $event->interaction->song->artist->name),
            $event->interaction->user->lastfm_session_key,
            $event->interaction->liked
        );
    }
}
