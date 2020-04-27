<?php

namespace App\Jobs;

use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateLastfmNowPlayingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $song;

    public function __construct(User $user, Song $song)
    {
        $this->user = $user;
        $this->song = $song;
    }

    public function handle(LastfmService $lastfmService): void
    {
        $lastfmService->updateNowPlaying(
            $this->song->artist->name,
            $this->song->title,
            $this->song->album->name === Album::UNKNOWN_NAME ? '' : $this->song->album->name,
            $this->song->length,
            $this->user->lastfm_session_key
        );
    }
}
