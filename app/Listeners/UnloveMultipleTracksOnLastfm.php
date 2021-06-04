<?php

namespace App\Listeners;

use App\Events\SongsBatchUnliked;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UnloveMultipleTracksOnLastfm implements ShouldQueue
{
    private $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongsBatchUnliked $event): void
    {
        $this->lastfm->batchToggleLoveTracks($event->songs, $event->user, false);
    }
}
