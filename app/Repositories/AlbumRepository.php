<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Repository<Album>
 * @implements ScoutableRepository<Album>
 */
class AlbumRepository extends Repository implements ScoutableRepository
{
    /** @param int $id */
    public function getOne($id, ?User $scopedUser = null): Model
    {
        $scopedUser ??= auth()->user();

        return $this->getOneBy([
            'id' => $id,
            'user_id' => $scopedUser?->id,
        ]);
    }

    /** @return Collection|array<array-key, Album> */
    public function getRecentlyAdded(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->isStandard()
            ->accessibleBy($user ?? $this->auth->user())
            ->distinct()
            ->latest('albums.created_at')
            ->limit($count)
            ->get('albums.*');
    }

    /** @return Collection|array<array-key, Album> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Album::query()
            ->isStandard()
            ->accessibleBy($user)
            ->withPlayCount($user)
            ->addSelect('albums.*')
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Album> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $albums = Album::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('albums.id', $ids)
            ->distinct()
            ->get('albums.*');

        return $preserveOrder ? $albums->orderByArray($ids) : $albums;
    }

    /** @return Collection|array<array-key, Album> */
    public function getByArtist(Artist $artist, ?User $user = null): Collection
    {
        return Album::query()
            ->accessibleBy($user ?? $this->auth->user())
            ->where(static function (Builder $query) use ($artist): void {
                $query->whereBelongsTo($artist)
                    ->orWhereHas('songs', static function (Builder $songQuery) use ($artist): void {
                        $songQuery->whereBelongsTo($artist);
                    });
            })
            ->orderBy('albums.name')
            ->distinct()
            ->get('albums.*');
    }

    public function getForListing(string $sortColumn, string $sortDirection, ?User $user = null): Paginator
    {
        return Album::query()
            ->accessibleBy($user ?? $this->auth->user())
            ->isStandard()
            ->sort($sortColumn, $sortDirection)
            ->distinct()
            ->select('albums.*')
            ->simplePaginate(21);
    }

    /** @return Collection<Album>|array<array-key, Album> */
    public function search(string $keywords, int $limit, ?User $scopedUser = null): Collection
    {
        return $this->getMany(
            ids: Album::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
            user: $scopedUser,
        );
    }
}
