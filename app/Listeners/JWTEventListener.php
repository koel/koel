<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;

class JWTEventListener
{
    /**
     * Handle user login events.
     *
     * @param Dispatcher $event
     */
    public function onValidUser($event)
    {
        auth()->setUser($event->user);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'tymon.jwt.valid',
            'App\Listeners\JWTEventListener@onValidUser'
        );
    }
}
