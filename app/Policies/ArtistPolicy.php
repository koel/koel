<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;

class ArtistPolicy
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
    ) {}

    public function access(User $user, Artist $artist): bool
    {
        return License::isCommunity() || $this->artistRepository->isAccessibleByUser($artist, $user);
    }

    public function embed(User $user, Artist $artist): bool
    {
        return License::isCommunity() || $artist->belongsToUser($user);
    }

    public function update(User $user, Artist $artist): bool
    {
        if (!$artist->is_standard) {
            return false;
        }

        // For CE, if the user can manage songs, they can update any artist.
        if ($user->hasPermissionTo(Permission::MANAGE_SONGS) && License::isCommunity()) {
            return true;
        }

        // For Plus, only the owner of the artist can update it.
        return $artist->belongsToUser($user) && License::isPlus();
    }

    public function edit(User $user, Artist $artist): bool
    {
        return $this->update($user, $artist);
    }
}
