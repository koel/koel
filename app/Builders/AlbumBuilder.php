<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Webmozart\Assert\Assert;

class AlbumBuilder extends Builder
{
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
    ];

    public function isStandard(): self
    {
        return $this->whereNot('albums.name', Album::UNKNOWN_NAME);
    }

    public function accessibleBy(User $user): self
    {
        if (License::isCommunity()) {
            // With the Community license, all albums are accessible by all users.
            return $this;
        }

        if (!$user->preferences->includePublicMedia) {
            // If the user does not want to include public media, we only return albums
            // that belong to them.
            return $this->whereBelongsTo($user);
        }

        // otherwise, we return albums that belong to the user or
        // albums that have at least one public song owned by the user in the same organization.
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
        return $this->leftJoin('songs as songs_for_playcount', 'albums.id', 'songs_for_playcount.album_id')
            ->join('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('songs_for_playcount.id', 'interactions.song_id')->where('interactions.user_id', $user->id);
            })
            ->groupBy('albums.id')
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

        return $this
            ->orderBy($column, $direction)
            // Depending on the column, we might need to order by the album's name as well.
            ->when($column === 'albums.artist_name', static fn (self $query) => $query->orderBy('albums.name'))
            ->when($column === 'albums.year', static fn (self $query) => $query->orderBy('albums.name'));
    }
}
