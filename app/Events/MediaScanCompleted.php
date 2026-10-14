<?php

namespace App\Events;

use App\Values\Scanning\ScanSummary;

class MediaScanCompleted extends Event
{
    public function __construct(
        public ScanSummary $summary,
    ) {}

    /**
     * The scan's recorded files are cleared as soon as dispatching returns,
     * so this event is handled in-process only.
     */
    public function broadcastWhen(): bool
    {
        return false;
    }
}
