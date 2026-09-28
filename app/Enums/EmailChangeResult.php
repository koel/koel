<?php

namespace App\Enums;

enum EmailChangeResult
{
    case CHANGED;
    case OUTDATED;
    case TAKEN;
}
