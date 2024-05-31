<?php

namespace App\Listeners;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Services\PlaylistService;
use Illuminate\Contracts\Queue\ShouldQueue;

class MakePlaylistSongsPublic implements ShouldQueue
{
    public function __construct(private readonly PlaylistService $service)
    {
    }

    public function handle(NewPlaylistCollaboratorJoined $event): void
    {
        $this->service->makePlaylistContentPublic($event->token->playlist);
    }
}
