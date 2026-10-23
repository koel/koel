<?php

namespace App\Enums;

enum FileSizeFillResult
{
    case FILLED;
    case MISSING;
    case SKIPPED;
}
