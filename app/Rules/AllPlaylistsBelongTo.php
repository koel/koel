<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

final class AllPlaylistsBelongTo implements Rule
{
    public function __construct(private User $user)
    {
    }

    /** @param array<int> $value */
    public function passes($attribute, $value): bool
    {
        return array_diff(Arr::wrap($value), $this->user->playlists->pluck('id')->toArray()) === [];
    }

    public function message(): string
    {
        return 'Not all playlists belong to the user';
    }
}
