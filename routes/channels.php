<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{publicId}', static function (User $user, $publicId): bool {
    return $user->is(User::query()->where('public_id', $publicId)->firstOrFail());
});

// Remote control is peer-to-peer: a user's devices exchange client-* events here and the server
// never sees them.
Broadcast::channel('koel.{publicId}', static function (User $user, string $publicId): bool {
    return $user->public_id === $publicId;
});
