<?php

namespace App\Listeners;

use App\Events\SongsBatchUnliked;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UnloveMultipleTracksOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(SongsBatchUnliked $event): void
    {
        $this->lastfm->batchToggleLoveTracks($event->songs, $event->user, false);
    }
}
