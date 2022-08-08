<?php

namespace App\Jobs;

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

    public function __construct(public User $user, public Song $song, public int $timestamp)
    {
    }

    public function handle(LastfmService $lastfmService): void
    {
        $lastfmService->scrobble($this->song, $this->user, $this->timestamp);
    }
}
