<?php

namespace App\Events;

use App\Values\SyncResultCollection;
use Illuminate\Queue\SerializesModels;

class MediaSyncCompleted extends Event
{
    use SerializesModels;

    public function __construct(public SyncResultCollection $results)
    {
    }
}
