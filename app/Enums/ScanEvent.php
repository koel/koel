<?php

namespace App\Enums;

enum ScanEvent
{
    case PATHS_GATHERED;
    case SCAN_PROGRESS;
    case SCAN_COMPLETED;
}
