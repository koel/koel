<?php

namespace App\Events;

use App\Values\SyncResult;
use Illuminate\Queue\SerializesModels;

class MediaSyncCompleted extends Event
{
    use SerializesModels;

    public SyncResult $result;

    public function __construct(SyncResult $result)
    {
        $this->result = $result;
    }
}
