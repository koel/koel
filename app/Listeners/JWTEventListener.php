<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;

class JWTEventListener
{
    public function onValidUser(Dispatcher $event): void
    {
        auth()->setUser($event->user);
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('tymon.jwt.valid', self::class.'@onValidUser');
    }
}
