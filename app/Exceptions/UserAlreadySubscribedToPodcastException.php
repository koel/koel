<?php

namespace App\Exceptions;

use App\Models\Podcast;
use App\Models\User;
use Exception;

final class UserAlreadySubscribedToPodcastException extends Exception
{
    public static function create(User $user, Podcast $podcast): self
    {
        return new self("User {$user->id} has already subscribed to podcast {$podcast->id}");
    }
}
