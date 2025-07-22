<?php

namespace App\Listeners;

use App\Events\SongFavoriteToggled;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class LoveTrackOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(SongFavoriteToggled $event): void
    {
        if (
            $event->song->isEpisode()
            || !LastfmService::enabled()
            || !$event->user->preferences->lastFmSessionKey
            || $event->song->artist->is_unknown
        ) {
            return;
        }

        $this->lastfm->toggleLoveTrack($event->song, $event->user, $event->favorite);
    }
}
