<?php

namespace App\Repositories;

use App\Facades\License;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection as SupportCollection;

/** @extends Repository<Playlist> */
class PlaylistRepository extends Repository
{
    /** @return Collection<int, Playlist> */
    public function getAllAccessibleByUser(User $user): Collection
    {
        return Playlist::query()->whereIn(
            'playlists.id',
            $this->accessibleByUser($user)->select('playlists.id'),
        )->get();
    }

    public function searchAccessibleByName(string $name, User $user): ?Playlist
    {
        return $this->accessibleByUser($user)->where('playlists.name', 'like', "%{$name}%")->first();
    }

    /**
     * @param array<string> $playableIds
     *
     * @return SupportCollection<string, int> positions keyed by playable ID
     */
    public function getPlayablePositionsInPlaylist(Playlist $playlist, array $playableIds): SupportCollection
    {
        return collect($playableIds)
            ->chunk(1000)
            ->flatMap(
                static fn (SupportCollection $chunk): SupportCollection => $playlist
                    ->playables()
                    ->newPivotQuery()
                    ->whereIn('song_id', $chunk->values())
                    ->pluck('position', 'song_id'),
            )
            ->map(static fn (int|string $position): int => (int) $position);
    }

    private function accessibleByUser(User $user): BelongsToMany
    {
        return License::isCommunity() ? $user->ownedPlaylists() : $user->playlists();
    }
}
