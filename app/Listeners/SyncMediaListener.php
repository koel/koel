<?php

namespace App\Listeners;

use App\Events\MediaPathChanged;
use App\Facades\Media;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncMediaListener implements ShouldQueue
{
    /**
     * Handle the MediaPathChanged event.
     *
     * @param  MediaPathChanged  $event
     * @return void
     */
    public function handle(MediaPathChanged $event)
    {
        Media::sync();
    }
}
