<?php

namespace App\Listeners;

use App\Events\SongsBatchLiked;
use App\Models\Song;
use App\Services\LastfmService;
use App\Values\LastfmLoveTrackParameters;
use Illuminate\Contracts\Queue\ShouldQueue;

class LoveMultipleTracksOnLastfm implements ShouldQueue
{
    private LastfmService $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongsBatchLiked $event): void
    {
        $this->lastfm->batchToggleLoveTracks(
            $event->songs->map(static function (Song $song): LastfmLoveTrackParameters {
                return LastfmLoveTrackParameters::make($song->title, $song->artist->name);
            }),
            $event->user
        );
    }
}
