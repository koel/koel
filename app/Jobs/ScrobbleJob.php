<?php

namespace App\Jobs;

use App\Models\Album;
use App\Models\Song;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrobbleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $user;
    private $song;
    private $timestamp;

    public function __construct(User $user, Song $song, int $timestamp)
    {
        $this->user = $user;
        $this->song = $song;
        $this->timestamp = $timestamp;
    }

    public function handle(LastfmService $lastfmService): void
    {
        $lastfmService->scrobble(
            $this->song->artist->name,
            $this->song->title,
            $this->timestamp,
            $this->song->album->name === Album::UNKNOWN_NAME ? '' : $this->song->album->name,
            $this->user->lastfm_session_key
        );
    }
}
