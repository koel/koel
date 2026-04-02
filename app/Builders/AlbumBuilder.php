<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;
use Webmozart\Assert\Assert;

class AlbumBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const array SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'albums.name',
        'year' => 'albums.year',
        'created_at' => 'albums.created_at',
        'artist_name' => 'albums.artist_name',
    ];

    private const array VALID_SORT_COLUMNS = [
        'albums.name',
        'albums.year',
        'albums.created_at',
        'albums.artist_name',
        'favorite', // alias column for favorite status
    ];

    public function onlyStandard(): self
    {
        return $this->whereNot('albums.name', Album::UNKNOWN_NAME);
    }

    private function accessible(): self
    {
        if (License::isCommunity()) {
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible albums.'));

        if (!$this->user->preferences->includePublicMedia) {
            return $this->whereBelongsTo($this->user);
        }

        return $this->where(function (Builder $query): void {
            $query
                ->whereBelongsTo($this->user)
                ->orWhereExists(function (QueryBuilder $sub): void {
                    $sub
                        ->select(DB::raw(1))
                        ->from('songs')
                        ->join('users', 'songs.owner_id', 'users.id')
                        ->whereColumn('songs.album_id', 'albums.id')
                        ->where('songs.is_public', true)
                        ->where('users.organization_id', $this->user->organization_id)
                        ->where('songs.owner_id', '<>', $this->user->id);
                });
        });
    }

    private function withPlayCount($includingFavoriteStatus = false): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        $groupColumns = $includingFavoriteStatus ? ['albums.id', 'favorites.created_at'] : ['albums.id'];

        // As we might have joined the `songs` table already, use an alias for the `songs` table
        // in this join to avoid conflicts.
        return $this
            ->leftJoin('songs as songs_for_playcount', 'albums.id', 'songs_for_playcount.album_id')
            ->leftJoin('interactions', function (JoinClause $join): void {
                $join->on('songs_for_playcount.id', 'interactions.song_id')->where(
                    'interactions.user_id',
                    $this->user->id,
                );
            })
            ->groupBy($groupColumns)
            ->addSelect(DB::raw('COALESCE(SUM(interactions.play_count), 0) as play_count'));
    }

    public function withUserContext(
        User $user,
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = false,
    ): self {
        $this->user = $user;

        return $this
            ->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount($includeFavoriteStatus));
    }

    private static function normalizeSortColumn(string $column): string
    {
        return array_key_exists($column, self::SORT_COLUMNS_NORMALIZE_MAP)
            ? self::SORT_COLUMNS_NORMALIZE_MAP[$column]
            : $column;
    }

    public function sort(string $column, string $direction): self
    {
        $column = self::normalizeSortColumn($column);

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        return $this
            ->orderBy($column, $direction)
            ->when($column === 'albums.artist_name', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'albums.year', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'favorite', static fn (self $query) => $query->orderBy('albums.name'));
    }
}
