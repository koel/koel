<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{publicId}', static function (User $user, $publicId): bool {
    return $user->is(User::query()->where('public_id', $publicId)->firstOrFail());
});

// Remote control is peer-to-peer: the browsers exchange client-* events over this channel and the
// server never sees them. Every user of the installation shares it, with the event name carrying
// the user id.
Broadcast::channel('koel', static fn (User $user): bool => (bool) $user);
