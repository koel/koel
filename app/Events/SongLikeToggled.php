<?php

namespace App\Events;

use App\Models\Interaction;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SongLikeToggled extends Event
{
    use SerializesModels;

    public $interaction;
    public $user;

    public function __construct(Interaction $interaction, User $user)
    {
        $this->interaction = $interaction;
        $this->user = $user;
    }
}
