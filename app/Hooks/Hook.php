<?php

namespace App\Hooks;

enum Hook: string
{
    case INITIAL_DATA_FETCHED = 'initial-data-fetched';
}
