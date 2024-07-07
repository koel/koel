<?php

namespace App\Enums;

enum ScanResultType: string
{
    case SUCCESS = 'Success';
    case ERROR = 'Error';
    case SKIPPED = 'Skipped';
}
