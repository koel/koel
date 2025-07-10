<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class ArtistBuilder extends Builder
{
    public const SORT_COLUMNS_NORMALIZE_MAP = [
        'name' => 'artists.name',
        'created_at' => 'artists.created_at',
    ];

    private const VALID_SORT_COLUMNS = [
        'artists.name',
        'artists.created_at',
    ];

    public function isStandard(): self
    {
        return $this->whereNotIn('artists.name', [Artist::UNKNOWN_NAME, Artist::VARIOUS_NAME]);
    }

    public function accessibleBy(User $user): self
    {
        if (License::isCommunity()) {
            // With the Community license, all artists are accessible by all users.
            return $this;
        }

        if (!$user->preferences->includePublicMedia) {
            // If the user does not want to include public media, we only return artists
            // that belong to them.
            return $this->whereBelongsTo($user);
        }

        // otherwise, we return artists that belong to the user or
        // artists who have at least one public song owned by the user in the same organization.
        return $this->where(static function (Builder $query) use ($user): void {
            $query->whereBelongsTo($user)
                ->orWhereHas('songs', static function (Builder $q) use ($user): void {
                    $q->where('songs.is_public', true)
                        ->whereHas('owner', static function (Builder $owner) use ($user): void {
                            $owner->where('organization_id', $user->organization_id)
                                ->where('owner_id', '<>', $user->id);
                        });
                });
        });
    }

    public function withPlayCount(User $user, string $aliasName = 'play_count'): self
    {
        // As we might have joined the songs table already, use an alias for the songs table
        // in this join to avoid conflicts.
        return $this->leftJoin('songs as songs_for_playcount', 'artists.id', 'songs_for_playcount.artist_id')
            ->join('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('interactions.song_id', 'songs_for_playcount.id')->where('interactions.user_id', $user->id);
            })
            ->groupBy('artists.id')
            ->addSelect(DB::raw("SUM(interactions.play_count) as $aliasName"));
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
}
