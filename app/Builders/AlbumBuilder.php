<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

        return $this->whereBelongsTo($user);
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
