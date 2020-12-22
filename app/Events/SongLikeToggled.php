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

    public function __construct(Interaction $interaction, ?User $user = null)
    {
        $this->interaction = $interaction;
        $this->user = $user ?: auth()->user();
    }
}
