<?php

namespace App\Rules;

use App\Facades\License;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

final class AllPlaylistsAreAccessibleBy implements ValidationRule
{
    public function __construct(private readonly User $user)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $accessiblePlaylists = $this->user->playlists;

        if (License::isPlus()) {
            $accessiblePlaylists = $accessiblePlaylists->merge($this->user->collaboratedPlaylists);
        }

        if (array_diff(Arr::wrap($value), $accessiblePlaylists->pluck('id')->toArray())) {
            $fail(
                License::isPlus()
                    ? 'Not all playlists are accessible by the user'
                    : 'Not all playlists belong to the user'
            );
        }
    }
}
