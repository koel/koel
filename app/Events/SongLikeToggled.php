<?php

namespace App\Events;

use App\Models\Interaction;

class SongLikeToggled extends Event
{
    public function __construct(public Interaction $interaction)
    {
    }
}
