<?php

namespace App\Listeners;

use App\Events\PlaybackStarted;
use App\Services\ScrobbleService;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class UpdateNowPlaying implements ShouldQueue
{
    public function __construct(
        private ScrobbleService $scrobbleService,
    ) {}

    public function handle(PlaybackStarted $event): void
    {
        if ($event->song->isEpisode() || $event->song->artist?->is_unknown) {
            return;
        }

        $this->scrobbleService->updateNowPlaying($event->song, $event->user);
    }
}
