<?php

namespace App\Events;

use App\Models\Interaction;
use Illuminate\Queue\SerializesModels;

class SongLikeToggled extends Event
{
    use SerializesModels;

    public function __construct(public Interaction $interaction)
    {
    }
}
