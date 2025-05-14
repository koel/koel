<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
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
        return $this->whereNotIn('artists.id', [Artist::UNKNOWN_ID, Artist::VARIOUS_ID]);
    }

    public function accessibleBy(User $user): self
    {
        if (License::isCommunity()) {
            // With the Community license, all artists are accessible by all users.
            return $this;
        }

        return $this->join('songs', static function (JoinClause $join) use ($user): void {
            $join->on('artists.id', 'songs.artist_id')
                ->where(static function (JoinClause $query) use ($user): void {
                    $query->where('songs.owner_id', $user->id)
                        ->orWhere('songs.is_public', true);
                });
        });
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
