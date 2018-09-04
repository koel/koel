<?php

namespace App\Listeners;

use App\Events\SongStartedPlaying;
use App\Models\Album;
use App\Services\LastfmService;

class UpdateLastfmNowPlaying
{
    protected $lastfm;

    public function __construct(LastfmService $lastfm)
    {
        $this->lastfm = $lastfm;
    }

    public function handle(SongStartedPlaying $event): void
    {
        if (!$this->shouldHandle($event)) {
            return;
        }

        $this->lastfm->updateNowPlaying(
            $event->song->artist->name,
            $event->song->title,
            $event->song->album->name === Album::UNKNOWN_NAME ? '' : $event->song->album->name,
            $event->song->length,
            $this->lastfm->getUserSessionKey($event->user)
        );
    }

    private function shouldHandle(SongStartedPlaying $event): bool
    {
        return $this->lastfm->enabled()
            && $this->lastfm->isUserConnected($event->user)
            && !$event->song->artist->is_unknown;
    }
}
