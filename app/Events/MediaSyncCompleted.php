<?php

namespace App\Events;

use App\Values\SyncResult;
use Illuminate\Queue\SerializesModels;

class MediaSyncCompleted extends Event
{
    use SerializesModels;

    public function __construct(public SyncResult $result)
    {
    }
}
