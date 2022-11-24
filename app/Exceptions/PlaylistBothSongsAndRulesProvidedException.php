<?php

namespace App\Exceptions;

use InvalidArgumentException;

class PlaylistBothSongsAndRulesProvidedException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('A playlist cannot have both songs and rules');
    }
}
