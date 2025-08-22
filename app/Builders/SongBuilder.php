<?php

namespace App\Builders;

use App\Builders\Concerns\CanScopeByUser;
use App\Facades\License;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Webmozart\Assert\Assert;

class SongBuilder extends FavoriteableBuilder
{
    use CanScopeByUser;

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

    public function inDirectory(string $path): self
    {
        // Make sure the path ends with a directory separator.
        $path = Str::finish(trim($path), DIRECTORY_SEPARATOR);

        return $this->where('path', 'LIKE', "$path%");
    }

    private function withPlayCount(): self
    {
        throw_unless($this->user, new LogicException('User must be set to query play counts.'));

        return $this
            ->leftJoin('interactions', function (JoinClause $join): void {
                $join->on('interactions.song_id', 'songs.id')->where('interactions.user_id', $this->user->id);
            })
            ->addSelect(DB::raw('COALESCE(interactions.play_count, 0) as play_count'));
    }

    public function accessible(): self
    {
        if (License::isCommunity()) {
            // In the Community Edition, all songs are accessible by all users.
            return $this;
        }

        throw_unless($this->user, new LogicException('User must be set to query accessible songs.'));

        // We want to alias both podcasts and podcast_user tables to avoid possible conflicts with other joins.
        $this->leftJoin('podcasts as podcasts_a11y', 'songs.podcast_id', 'podcasts_a11y.id')
            ->leftJoin('podcast_user as podcast_user_a11y', function (JoinClause $join): void {
                $join->on('podcasts_a11y.id', 'podcast_user_a11y.podcast_id')
                    ->where('podcast_user_a11y.user_id', $this->user->id);
            })->whereNot(static function (self $query): void {
                // Episodes must belong to a podcast that the user is subscribed to.
                $query->whereNotNull('songs.podcast_id')->whereNull('podcast_user_a11y.podcast_id');
            });

        // If the song is a podcast episode, we need to ensure that the user has access to it.
        return $this->where(function (self $query): void {
            $query->whereNotNull('songs.podcast_id')
                ->orWhere(function (self $q2) {
                    // Depending on the user preferences, the song must be either:
                    // - owned by the user, or
                    // - shared (is_public=true) by the users in the same organization
                    if (!$this->user->preferences->includePublicMedia) {
                        return $q2->whereBelongsTo($this->user, 'owner');
                    }

                    return $q2->where(function (self $q3): void {
                        $q3->whereBelongsTo($this->user, 'owner')
                            ->orWhere(function (self $q4): void {
                                $q4->where('songs.is_public', true)
                                    ->whereHas('owner', function (Builder $owner): void {
                                        $owner->where('organization_id', $this->user->organization_id)
                                            ->where('owner_id', '<>', $this->user->id);
                                    });
                            });
                    });
                });
        });
    }

    public function withUserContext(
        bool $includeFavoriteStatus = true,
        bool $favoritesOnly = false,
        bool $includePlayCount = true,
    ): self {
        return $this->accessible()
            ->when($includeFavoriteStatus, static fn (self $query) => $query->withFavoriteStatus($favoritesOnly))
            ->when($includePlayCount, static fn (self $query) => $query->withPlayCount());
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
}
