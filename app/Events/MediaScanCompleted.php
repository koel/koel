<?php

namespace App\Events;

use App\Values\Scanning\ScanResultCollection;

class MediaScanCompleted extends Event
{
    public function __construct(
        public ScanResultCollection $results,
    ) {}

    /**
     * The results cover every file in the library, far too much to serialize onto a queue,
     * so this event is handled in-process only.
     */
    public function broadcastWhen(): bool
    {
        return false;
    }
}
