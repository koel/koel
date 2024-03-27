<?php

namespace App\Rules;

use App\Facades\License;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;

final class AllPlaylistsAreAccessibleBy implements Rule
{
    public function __construct(private User $user)
    {
    }

    /** @param array<int> $value */
    public function passes($attribute, $value): bool
    {
        $accessiblePlaylists = $this->user->playlists;

        if (License::isPlus()) {
            $accessiblePlaylists = $accessiblePlaylists->merge($this->user->collaboratedPlaylists);
        }

        return array_diff(Arr::wrap($value), $accessiblePlaylists->pluck('id')->toArray()) === [];
    }

    public function message(): string
    {
        return License::isPlus()
            ? 'Not all playlists are accessible by the user'
            : 'Not all playlists belong to the user';
    }
}
