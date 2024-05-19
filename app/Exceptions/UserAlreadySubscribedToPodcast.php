<?php

namespace App\Exceptions;

use App\Models\Podcast\Podcast;
use App\Models\User;
use Exception;

final class UserAlreadySubscribedToPodcast extends Exception
{
    public static function make(User $user, Podcast $podcast): self
    {
        return new self("User $user->id has already subscribed to podcast $podcast->id");
    }
}
