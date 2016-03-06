<?php

namespace App\Events;

use App\Models\Song;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SongStartedPlaying extends Event
{
    use SerializesModels;

    /**
     * The now playing song.
     *
     * @var Song
     */
    public $song;

    /**
     * The user listening.
     *
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param Song $song
     * @param User $user
     */
    public function __construct(Song $song, User $user)
    {
        $this->song = $song;
        $this->user = $user;
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
