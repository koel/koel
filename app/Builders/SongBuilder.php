<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Webmozart\Assert\Assert;

/**
 * @method self logSql()
 */
class SongBuilder extends Builder
{
    public const SORT_COLUMNS_NORMALIZE_MAP = [
        'title' => 'songs.title',
        'track' => 'songs.track',
        'length' => 'songs.length',
        'created_at' => 'songs.created_at',
        'disc' => 'songs.disc',
        'artist_name' => 'artists.name',
        'album_name' => 'albums.name',
    ];

    private const VALID_SORT_COLUMNS = [
        'songs.title',
        'songs.track',
        'songs.length',
        'songs.created_at',
        'artists.name',
        'albums.name',
    ];

    public function inDirectory(string $path): static
    {
        // Make sure the path ends with a directory separator.
        $path = rtrim(trim($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $this->where('path', 'LIKE', "$path%");
    }

    public function withMetaFor(User $user, bool $requiresInteractions = false): static
    {
        $joinType = $requiresInteractions ? 'join' : 'leftJoin';

        return $this
            ->with('artist', 'album', 'album.artist')
            ->$joinType('interactions', static function (JoinClause $join) use ($user): void {
                $join->on('interactions.song_id', '=', 'songs.id')
                    ->where('interactions.user_id', $user->id);
            })
            ->join('albums', 'songs.album_id', '=', 'albums.id')
            ->join('artists', 'songs.artist_id', '=', 'artists.id')
            ->select(
                'songs.*',
                'albums.name',
                'artists.name',
                'interactions.liked',
                'interactions.play_count'
            );
    }

    public function accessibleBy(User $user, bool $withTableName = true): static
    {
        if (License::isCommunity()) {
            // In the Community Edition, all songs are accessible by all users.
            return $this;
        }

        return $this->where(static function (Builder $query) use ($user, $withTableName): void {
            $query->where(($withTableName ? 'songs.' : '') . 'is_public', true)
                ->orWhere(($withTableName ? 'songs.' : '') . 'owner_id', $user->id);
        });
    }

    public function sort(string $column, string $direction): static
    {
        $column = self::normalizeSortColumn($column);

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        $this->orderBy($column, $direction);

        if ($column === 'artists.name') {
            $this->orderBy('albums.name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title');
        } elseif ($column === 'albums.name') {
            $this->orderBy('artists.name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title');
        } elseif ($column === 'track') {
            $this->orderBy('songs.disc')
                ->orderBy('songs.track');
        }

        return $this;
    }

    private static function normalizeSortColumn(string $column): string
    {
        return key_exists($column, self::SORT_COLUMNS_NORMALIZE_MAP)
            ? self::SORT_COLUMNS_NORMALIZE_MAP[$column]
            : $column;
    }

    public function hostedOnS3(): static
    {
        return $this->where('path', 'LIKE', 's3://%');
    }
}
