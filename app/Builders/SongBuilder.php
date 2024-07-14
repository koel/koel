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
        'podcast_title' => 'podcasts.title',
        'podcast_author' => 'podcasts.author',
    ];

    private const VALID_SORT_COLUMNS = [
        'songs.title',
        'songs.track',
        'songs.length',
        'songs.created_at',
        'artists.name',
        'albums.name',
        'podcasts.title',
        'podcasts.author',
    ];

    private User $user;

    public function inDirectory(string $path): self
    {
        // Make sure the path ends with a directory separator.
        $path = rtrim(trim($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $this->where('path', 'LIKE', "$path%");
    }

    public function withMeta(bool $requiresInteractions = false): self
    {
        $joinClosure = function (JoinClause $join): void {
            $join->on('interactions.song_id', 'songs.id')->where('interactions.user_id', $this->user->id);
        };

        return $this
            ->with('artist', 'album', 'album.artist')
            ->when(
                $requiresInteractions,
                static fn (self $query) => $query->join('interactions', $joinClosure),
                static fn (self $query) => $query->leftJoin('interactions', $joinClosure)
            )
            ->leftJoin('albums', 'songs.album_id', 'albums.id')
            ->leftJoin('artists', 'songs.artist_id', 'artists.id')
            ->select(
                'songs.*',
                'albums.name',
                'artists.name',
                'interactions.liked',
                'interactions.play_count'
            );
    }

    public function accessible(): self
    {
        if (License::isCommunity()) {
            // In the Community Edition, all songs are accessible by all users.
            return $this;
        }

        // We want to alias both podcasts and podcast_user tables to avoid possible conflicts with other joins.
        return $this->leftJoin('podcasts as podcasts_a11y', 'songs.podcast_id', 'podcasts_a11y.id')
            ->leftJoin('podcast_user as podcast_user_a11y', function (JoinClause $join): void {
                $join->on('podcasts_a11y.id', 'podcast_user_a11y.podcast_id')
                    ->where('podcast_user_a11y.user_id', $this->user->id);
            })
            ->where(function (Builder $query): void {
                // Songs must be public or owned by the user.
                $query->where('songs.is_public', true)
                    ->orWhere('songs.owner_id', $this->user->id);
            })->whereNot(static function (Builder $query): void {
                // Episodes must belong to a podcast that the user is not subscribed to.
                $query->whereNotNull('songs.podcast_id')
                    ->whereNull('podcast_user_a11y.podcast_id');
            });
    }

    private function sortByOneColumn(string $column, string $direction): self
    {
        $column = self::normalizeSortColumn($column);

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        return $this
            ->orderBy($column, $direction)
            ->when($column === 'artists.name', static fn (self $query) => $query->orderBy('albums.name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'albums.name', static fn (self $query) => $query->orderBy('artists.name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'track', static fn (self $query) => $query->orderBy('songs.disc')
                ->orderBy('songs.track'));
    }

    public function sort(array $columns, string $direction): self
    {
        $this->leftJoin('podcasts', 'songs.podcast_id', 'podcasts.id');

        foreach ($columns as $column) {
            $this->sortByOneColumn($column, $direction);
        }

        return $this;
    }

    private static function normalizeSortColumn(string $column): string
    {
        return key_exists($column, self::SORT_COLUMNS_NORMALIZE_MAP)
            ? self::SORT_COLUMNS_NORMALIZE_MAP[$column]
            : $column;
    }

    public function storedOnCloud(): self
    {
        return $this->whereNotNull('storage')
            ->where('storage', '!=', '');
    }

    public function forUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
