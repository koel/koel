<?php

namespace App\Events;

use App\Models\Interaction;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SongLikeToggled extends Event
{
    use SerializesModels;

    /**
     * The interaction (like/unlike) in action.
     *
     * @var Interaction
     */
    public $interaction;

    /**
     * The user who carries the action.
     *
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param Interaction $interaction
     * @param User        $user
     */
    public function __construct(Interaction $interaction, User $user = null)
    {
        $this->interaction = $interaction;
        $this->user = $user ?: auth()->user();
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
