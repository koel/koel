<?php

namespace App\Listeners;

use App\Events\SongsBatchLiked;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoveMultipleTracksOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(SongsBatchLiked $event): void
    {
        $this->lastfm->batchToggleLoveTracks($event->songs, $event->user, true);
    }
}
