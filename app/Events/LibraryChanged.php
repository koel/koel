<?php

namespace App\Events;

class LibraryChanged extends Event
{
    /**
     * Create a new event instance.
     */
    public function __construct()
    {
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
