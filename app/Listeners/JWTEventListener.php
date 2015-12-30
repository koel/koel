<?php

namespace App\Listeners;

class JWTEventListener
{
    /**
     * Handle user login events.
     */
    public function onValidUser($event) 
    {
        auth()->setUser($event->user);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'tymon.jwt.valid',
            'App\Listeners\JWTEventListener@onValidUser'
        );
    }
}
