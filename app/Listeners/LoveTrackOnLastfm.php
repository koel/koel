<?php

namespace App\Listeners;

use App\Events\SongLikeToggled;
use App\Services\LastfmService;

class LoveTrackOnLastfm
{
    protected $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongLikeToggled $event): void
    {
        if (!$this->shouldHandle($event)) {
            return;
        }

        $this->lastfm->toggleLoveTrack(
            $event->interaction->song->title,
            $event->interaction->song->artist->name,
            $this->lastfm->getUserSessionKey($event->user),
            $event->interaction->liked
        );
    }

    private function shouldHandle(SongLikeToggled $event): bool
    {
        return $this->lastfm->enabled()
            && $this->lastfm->isUserConnected($event->user)
            && !$event->interaction->song->artist->is_unknown;
    }
}
