<?php

namespace App\Listeners;

use App\Events\SongFavoriteToggled;
use App\Services\Integrations\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class LoveTrackOnLastfm implements ShouldQueue
{
    public function __construct(
        private LastfmService $lastfm,
    ) {}

    public function handle(SongFavoriteToggled $event): void
    {
        if (!$event->song->isScrobbleable() || !$this->lastfm->isConnected($event->user)) {
            return;
        }

        $this->lastfm->toggleLoveTrack($event->song, $event->user, $event->favorite);
    }
}
