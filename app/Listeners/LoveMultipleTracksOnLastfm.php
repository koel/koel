<?php

namespace App\Listeners;

use App\Events\MultipleSongsLiked;
use App\Models\Song;
use App\Services\Integrations\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class LoveMultipleTracksOnLastfm implements ShouldQueue
{
    public function __construct(
        private LastfmService $lastfm,
    ) {}

    public function handle(MultipleSongsLiked $event): void
    {
        $songs = $event->songs->filter(static fn (Song $song) => $song->isScrobbleable());

        if ($songs->isEmpty() || !$this->lastfm->isConnected($event->user)) {
            return;
        }

        $this->lastfm->batchToggleLoveTracks($songs, $event->user, true);
    }
}
