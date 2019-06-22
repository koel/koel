<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;

class JWTEventListener
{
    public function onValidUser(Dispatcher $event)
    {
        auth()->setUser($event->user);
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'tymon.jwt.valid',
            self::class.'@onValidUser'
        );
    }
}
