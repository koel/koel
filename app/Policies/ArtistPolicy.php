<?php

namespace App\Policies;

use App\Enums\Acl\Permission;
use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;

class ArtistPolicy
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
    ) {}

    public function access(User $user, Artist $artist): bool
    {
        return License::isCommunity() || $this->artistRepository->isAccessibleByUser($artist, $user);
    }

    public function embed(User $user, Artist $artist): bool
    {
        return !$user->isGuest() && $this->access($user, $artist);
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

    public function merge(User $user, Artist $source, Artist $target): bool
    {
        if (!$this->update($user, $source) || !$this->update($user, $target)) {
            return false;
        }

        return License::isCommunity() || $this->ownsEveryAlbumToMerge($user, $source, $target);
    }

    private function ownsEveryAlbumToMerge(User $user, Artist $source, Artist $target): bool
    {
        return $source->albums->every(function (Album $album) use ($user, $target): bool {
            $namesakeAlbum = $this->albumRepository->findOneByArtistAndNameIgnoringCase($target->id, $album->name);

            return $album->belongsToUser($user) && (!$namesakeAlbum || $namesakeAlbum->belongsToUser($user));
        });
    }
}
