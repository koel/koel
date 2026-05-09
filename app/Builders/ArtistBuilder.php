<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;
use Webmozart\Assert\Assert;

/**
 * @extends FavoriteableBuilder<Artist>
 */
class ArtistBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const array SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'artists.name',
        'created_at' => 'artists.created_at',
    ];

    private const array VALID_SORT_COLUMNS = [
        'artists.name',
        'artists.created_at',
    ];

    public function onlyStandard(): self
    {
        return $this->whereNotIn('artists.name', [Artist::UNKNOWN_NAME, Artist::VARIOUS_NAME]);
    }

    public function onlyAlbumArtists(): self
    {
        return $this->whereHas('albums');
    }

    private function accessible(): self
    {
        if (License::isCommunity()) {
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible artists.'));

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
                        ->whereColumn('songs.artist_id', 'artists.id')
                        ->where('songs.is_public', true)
                        ->where('users.organization_id', $this->user->organization_id)
                        ->where('songs.owner_id', '<>', $this->user->id);
                });
        });
    }

    private function withPlayCount(bool $includingFavoriteStatus = false): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        $groupColumns = $includingFavoriteStatus ? ['artists.id', 'favorites.created_at'] : ['artists.id'];

        // As we might have joined the `songs` table already, use an alias for the `songs` table
        // in this join to avoid conflicts.
        return $this
            ->leftJoin('songs as songs_for_playcount', 'artists.id', 'songs_for_playcount.artist_id')
            ->leftJoin('interactions', function (JoinClause $join): void {
                $join->on('interactions.song_id', 'songs_for_playcount.id')->where(
                    'interactions.user_id',
                    $this->user->id,
                );
            })
            ->groupBy($groupColumns)
            ->addSelect(DB::raw('COALESCE(SUM(interactions.play_count), 0) as play_count'));
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

        return $this->orderBy($column, $direction);
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
}
