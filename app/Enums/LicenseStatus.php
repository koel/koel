<?php

namespace App\Enums;

enum LicenseStatus
{
    case VALID;
    case INVALID;
    case NO_LICENSE;
    case UNKNOWN;
}
