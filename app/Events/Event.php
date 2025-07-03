<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Queue\SerializesModels;

abstract class Event implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use SerializesModels;

    public function broadcastOn(): array|Channel|null
    {
        return [];
    }
}
