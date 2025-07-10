<?php

namespace App\Builders;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

/**
 * @method self logSql()
 *
 * @extends Builder<Song>
 */
class SongBuilder extends Builder
{
    public const SORT_COLUMNS_NORMALIZE_MAP = [
        'title' => 'songs.title',
        'track' => 'songs.track',
        'length' => 'songs.length',
        'created_at' => 'songs.created_at',
        'disc' => 'songs.disc',
        'year' => 'songs.year',
        'artist_name' => 'songs.artist_name',
        'album_name' => 'songs.album_name',
        'podcast_title' => 'podcasts.title',
        'podcast_author' => 'podcasts.author',
        'genre' => 'genres.name',
    ];

    private const VALID_SORT_COLUMNS = [
        'songs.title',
        'songs.track',
        'songs.length',
        'songs.year',
        'songs.created_at',
        'songs.artist_name',
        'songs.album_name',
        'podcasts.title',
        'podcasts.author',
        'genres.name',
    ];

    private User $user;

    public function inDirectory(string $path): self
    {
        // Make sure the path ends with a directory separator.
        $path = Str::finish(trim($path), DIRECTORY_SEPARATOR);

        return $this->where('path', 'LIKE', "$path%");
    }

    public function withMetaData(): self
    {
        return $this
            ->with('artist', 'album', 'album.artist')
            ->leftJoin('interactions', function (JoinClause $join): void {
                $join->on('interactions.song_id', 'songs.id')->where('interactions.user_id', $this->user->id);
            })
            ->addSelect(
                'songs.*',
                'interactions.liked',
                'interactions.play_count',
            );
    }

    public function accessible(?User $user = null): self
    {
        if (License::isCommunity()) {
            // In the Community Edition, all songs are accessible by all users.
            return $this;
        }

        $user ??= $this->user;

        // We want to alias both podcasts and podcast_user tables to avoid possible conflicts with other joins.
        $this->leftJoin('podcasts as podcasts_a11y', 'songs.podcast_id', 'podcasts_a11y.id')
            ->leftJoin('podcast_user as podcast_user_a11y', static function (JoinClause $join) use ($user): void {
                $join->on('podcasts_a11y.id', 'podcast_user_a11y.podcast_id')
                    ->where('podcast_user_a11y.user_id', $user->id);
            })->whereNot(static function (self $query): void {
                // Episodes must belong to a podcast that the user is subscribed to.
                $query->whereNotNull('songs.podcast_id')->whereNull('podcast_user_a11y.podcast_id');
            });

        // Depending on the user preferences, songs must be either:
        // - owned by the user, or
        // - shared (is_public=true) by the users in the same organization
        if (!$user->preferences->includePublicMedia) {
            return $this->whereBelongsTo($user, 'owner');
        }

        return $this->where(static function (Builder $query) use ($user): void {
            $query->whereBelongsTo($user, 'owner')
                ->orWhere(static function (Builder $q) use ($user): void {
                    $q->where('songs.is_public', true)
                        ->whereHas('owner', static function (Builder $owner) use ($user): void {
                            $owner->where('organization_id', $user->organization_id)
                                ->where('owner_id', '<>', $user->id);
                        });
                });
        });
    }

    private function sortByOneColumn(string $column, string $direction): self
    {
        $column = self::normalizeSortColumn($column);

        Assert::oneOf($column, self::VALID_SORT_COLUMNS);
        Assert::oneOf(strtolower($direction), ['asc', 'desc']);

        return $this
            ->orderBy($column, $direction)
            // Depending on the column, we might need to order by other columns as well.
            ->when($column === 'songs.artist_name', static fn (self $query) => $query->orderBy('songs.album_name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'songs.album_name', static fn (self $query) => $query->orderBy('songs.artist_name')
                ->orderBy('songs.disc')
                ->orderBy('songs.track')
                ->orderBy('songs.title'))
            ->when($column === 'track', static fn (self $query) => $query->orderBy('songs.disc')
                ->orderBy('songs.track'));
    }

    public function sort(array $columns, string $direction): self
    {
        $this->when(
            in_array('podcast_title', $columns, true) || in_array('podcast_author', $columns, true),
            static fn (self $query) => $query->leftJoin('podcasts', 'songs.podcast_id', 'podcasts.id')
        )->when(
            in_array('genre', $columns, true),
            static fn (self $query) => $query
                ->leftJoin('genre_song', 'songs.id', 'genre_song.song_id')
                ->leftJoin('genres', 'genre_song.genre_id', 'genres.id')
        );

        foreach ($columns as $column) {
            $this->sortByOneColumn($column, $direction);
        }

        return $this;
    }

    private static function normalizeSortColumn(string $column): string
    {
        return array_key_exists($column, self::SORT_COLUMNS_NORMALIZE_MAP)
            ? self::SORT_COLUMNS_NORMALIZE_MAP[$column]
            : $column;
    }

    public function storedOnCloud(): self
    {
        return $this->whereNotNull('storage')
            ->where('storage', '!=', '')
            ->whereNull('podcast_id');
    }

    public function storedLocally(): self
    {
        return $this->where(static fn (self $query) => $query->whereNull('songs.storage')->orWhere('songs.storage', ''))
                ->whereNull('songs.podcast_id');
    }

    public function forUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
