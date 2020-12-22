<?php

namespace App\Events;

abstract class Event
{
    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array<mixed>
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
