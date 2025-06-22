<?php

namespace App\Events;

use App\Values\Scanning\ScanResultCollection;

class MediaScanCompleted extends Event
{
    public function __construct(public ScanResultCollection $results)
    {
    }
}
