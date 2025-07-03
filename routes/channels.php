<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{publicId}', static function (User $user, $publicId): bool {
    return $user->is(User::query()->where('public_id', $publicId)->firstOrFail());
});
