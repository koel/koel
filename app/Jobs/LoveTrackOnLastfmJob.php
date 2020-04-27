<?php

namespace App\Jobs;

use App\Models\Interaction;
use App\Models\User;
use App\Services\LastfmService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LoveTrackOnLastfmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $interaction;

    public function __construct(User $user, Interaction $interaction)
    {
        $this->user = $user;
        $this->interaction = $interaction;
    }

    public function handle(LastfmService $lastfmService): void
    {
        $lastfmService->toggleLoveTrack(
            $this->interaction->song->title,
            $this->interaction->song->artist->name,
            $this->user->lastfm_session_key,
            $this->interaction->liked
        );
    }
}
