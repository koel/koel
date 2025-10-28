<?php

namespace App\Events;

use App\Models\Podcast;
use App\Models\User;

class UserUnsubscribedFromPodcast extends Event
{
    public function __construct(public readonly User $user, public readonly Podcast $podcast)
    {
    }
}
