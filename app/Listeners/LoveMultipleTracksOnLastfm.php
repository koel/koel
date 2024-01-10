<?php

namespace App\Listeners;

use App\Events\MultipleSongsLiked;
use App\Services\LastfmService;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoveMultipleTracksOnLastfm implements ShouldQueue
{
    public function __construct(private LastfmService $lastfm)
    {
    }

    public function handle(MultipleSongsLiked $event): void
    {
        $this->lastfm->batchToggleLoveTracks($event->songs, $event->user, true);
    }
}
