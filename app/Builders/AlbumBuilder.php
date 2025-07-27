<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;
use Webmozart\Assert\Assert;

class AlbumBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'albums.name',
        'year' => 'albums.year',
        'created_at' => 'albums.created_at',
        'artist_name' => 'albums.artist_name',
    ];

    private const VALID_SORT_COLUMNS = [
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
            // With the Community license, all albums are accessible by all users.
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible albums.'));

        if (!$this->user->preferences->includePublicMedia) {
            // If the user does not want to include public media, we only return albums
            // that belong to them.
            return $this->whereBelongsTo($this->user);
        }

        // otherwise, we return albums that belong to the user or
        // albums that have at least one public song owned by the user in the same organization.
        return $this->where(function (Builder $query): void {
            $query->whereBelongsTo($this->user)
                ->orWhereHas('songs', function (Builder $q): void {
                    $q->where('songs.is_public', true)
                        ->whereHas('owner', function (Builder $owner): void {
                            $owner->where('organization_id', $this->user->organization_id)
                                ->where('owner_id', '<>', $this->user->id);
                        });
                });
        });
    }

    private function withPlayCount($includingFavoriteStatus = false): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        $groupColumns = $includingFavoriteStatus
            ? ['albums.id', 'favorites.created_at']
            : ['albums.id'];

        // As we might have joined the `songs` table already, use an alias for the `songs` table
        // in this join to avoid conflicts.
        return $this->leftJoin('songs as songs_for_playcount', 'albums.id', 'songs_for_playcount.album_id')
            ->join('interactions', function (JoinClause $join): void {
                $join->on('songs_for_playcount.id', 'interactions.song_id')
                    ->where('interactions.user_id', $this->user->id);
            })
            ->groupBy($groupColumns)
            ->addSelect(DB::raw("SUM(interactions.play_count) as play_count"));
    }

    public function withUserContext(
        User $user,
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = false,
    ): self {
        $this->user = $user;

        return $this->accessible()
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
            // Depending on the column, we might need to order by the album's name as well.
            ->when($column === 'albums.artist_name', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'albums.year', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'favorite', static fn (self $query) => $query->orderBy('albums.name'));
    }
}
