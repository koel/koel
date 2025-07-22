<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use LogicException;
use Webmozart\Assert\Assert;

class ArtistBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

    public const SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'artists.name',
        'created_at' => 'artists.created_at',
    ];

    private const VALID_SORT_COLUMNS = [
        'artists.name',
        'artists.created_at',
    ];

    public function onlyStandard(): self
    {
        return $this->whereNotIn('artists.name', [Artist::UNKNOWN_NAME, Artist::VARIOUS_NAME]);
    }

    private function accessible(): self
    {
        if (License::isCommunity()) {
            // With the Community license, all artists are accessible by all users.
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible artists.'));

        if (!$this->user->preferences->includePublicMedia) {
            // If the user does not want to include public media, we only return artists
            // that belong to them.
            return $this->whereBelongsTo($this->user);
        }

        // otherwise, we return artists that belong to the user or
        // artists who have at least one public song owned by the user in the same organization.
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

    private function withPlayCount(bool $includingFavoriteStatus = false): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        $groupColumns = $includingFavoriteStatus
            ? ['artists.id', 'favorites.created_at']
            : ['artists.id'];

        // As we might have joined the `songs` table already, use an alias for the `songs` table
        // in this join to avoid conflicts.
        return $this->leftJoin('songs as songs_for_playcount', 'artists.id', 'songs_for_playcount.artist_id')
            ->join('interactions', function (JoinClause $join): void {
                $join->on('interactions.song_id', 'songs_for_playcount.id')
                    ->where('interactions.user_id', $this->user->id);
            })
            ->groupBy($groupColumns)
            ->addSelect(DB::raw("SUM(interactions.play_count) as play_count"));
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

        return $this->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount($includeFavoriteStatus));
    }
}
