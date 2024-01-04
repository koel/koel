<?php

namespace App\Events;

use App\Values\ScanResultCollection;
use Illuminate\Queue\SerializesModels;

class MediaScanCompleted extends Event
{
    use SerializesModels;

    public function __construct(public ScanResultCollection $results)
    {
    }
}
